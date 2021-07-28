<?php
include('chat_functions.php');
include('chat_settings.php');
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, $port);
socket_listen($socket);
$clients = array($socket);

while (true) {
	$changed = $clients;

	socket_select($changed, $null, $null, 0, 10);

	if (in_array($socket, $changed)) {
		$client = socket_accept($socket);
		$clients[] = $client;
		$header = socket_read($client, 1024);
		echo "Connected from $client \r\n";
		handshake($header, $client);
		socket_getpeername($client, $ip);
		$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected')));
		broadcast_message($response);
		echo "Broadcast Welcome $response \r\n";
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}

	foreach ($changed as $changed_socket) {

		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1){
			$received_text = unmask($buf);
			$tst_msg = json_decode($received_text, true);
			$user_name = $tst_msg['name'];
			$user_message = $tst_msg['message'];
			$user_color = $tst_msg['color'];
			$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));

			broadcast_message($response_text);
			echo "$user_name - $response_text \r\n";
			break 2;
		}//end while

		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);

		if ($buf === false) { //if no data in buffer we are done
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);
			unset($clients[$found_socket]);
			$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
			echo "Disconnected $response \r\n";
			broadcast_message($response);

		}
	}//end foreach
}
socket_close($socket);

?>