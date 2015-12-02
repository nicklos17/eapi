<?php  
/** 
 * SocketServer Class 
 * By James.Huang <shagoo#gmail.com> 
**/  
set_time_limit(0);  
class SocketServer   
{  
    private static $socket;  
    function SocketServer($port)   
    {  
        global $errno, $errstr;  
        if ($port < 1024) {  
            die("Port must be a number which bigger than 1024/n");  
        }  
          
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                socket_bind($socket, '127.0.0.1', $port);
                socket_listen($socket,10);
        if (!$socket) die("$errstr ($errno)"); 


$conn =socket_accept($socket);
           socket_write($conn, 'id=3');
//while($a=socket_read($conn, 1024))
  //var_dump($a);
        //  var_dump($a);


          
   }  
}  
new SocketServer(2015); 