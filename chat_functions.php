<?php #chat functions
function broadcast_message($msg){
	global $clients;

	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));

	}
	return true;

}

function unmask($text) {
	$length = ord($text[1]) & 127;

	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}else{
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

function mask($text){
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if($length <= 125)
		$header = pack('CC', $b1, $length);

	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);

	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);

	return $header.$text;

}
function handshake($header,$client){ 

	preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $header, $matches);
	$key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
 	$headers = "HTTP/1.1 101 Switching Protocols\r\n";
    $headers .= "Upgrade: websocket\r\n";
    $headers .= "Connection: Upgrade\r\n";
    $headers .= "Sec-WebSocket-Version: 13\r\n";
    $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
	socket_write($client,$headers,strlen($headers));
	echo "Handshake Complete \r\n";
}
?>