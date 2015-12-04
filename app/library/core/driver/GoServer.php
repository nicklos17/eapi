<?php
namespace core\driver;

class GoServer
{
    private $result;
    private $socket;
    private $config;
    private $sendData = array();

    /**
     * 构造函数创建SCOKET连接
     */
    public function __construct() {
        $this->config = \core\Config::item('goServer');
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!is_resource($this->socket)) {
            throw new \Exception('Unable to create socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
            return;
        }

        if(!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1) ||
            !socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $this->config->timeout, 'usec' => 0)) ||
            !socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $this->config->timeout, 'usec' => 0))) {
            throw new \Exception('Unable to set socket option: ' . socket_strerror(socket_last_error()) . PHP_EOL);
            return;
        }

        if(!socket_connect($this->socket, $this->config->server, $this->config->port)) {
            throw new \Exception('Unable to connect go server: ' . socket_strerror(socket_last_error()) . PHP_EOL);
            return;
        }
    }

    /**
     * 发送并发请求前调用
     * @params $key string 并行处理标志，返回的数据格式也按照该key组织返回
     * @params $callName string 调用logic的类名和方法名 形式如：Domain->checkMyDomain
     * @params $params array 要发送的参数，为数组格式，必须是枚举数组
     *
     * @example
     *      $socket->call('ename.com', 'Domain->checkMyDomain', array('10000', 'ename.com'));
     *
     */
    public function call($key, $callName, $params = null) {
        if(!in_array($key, $this->sendData)) {
            $this->sendData[$key] = array();
        }

        if(!in_array($callName, $this->sendData[$key])) {
            $this->sendData[$key][$callName] = array();
        }

        $cm = explode('::', $callName);
        if(count($cm) != 2) {
             throw new \Exception('call function error: $callName format is falt' . PHP_EOL);
             return false;
        }

        $this->sendData[$key][$callName] = $params === null ? '' : json_encode($params);
        return true;
    }

    /**
     * 循环调用call函数后调用send真正发送数据，并等待数据返回
     * @return array 返回数据根据call的数据返回数组
     * @example
     *      $res = $socket->send();
     *      $res形式为 array('ename.com' => array('Domain->checkMyDomain' => false))
     *                  OR
     *                  array('ename.com' => array('Domain->checkMyDomain' => array('some result')))
     */
    public function send() {
        if(empty($this->sendData)) {
            $this->close();
            throw new \Exception('Unable to get send data' . PHP_EOL);
            return false;
        }

        if(!socket_write($this->socket, json_encode($this->sendData))) {
            $this->sendData = array();
            $this->close();
            throw new \Exception('Unable to write data to go server：' . socket_strerror(socket_last_error()) . PHP_EOL);
            return false;
        }

        $this->sendData = array();

        $res = socket_read($this->socket, $this->config->readLen);
        if(!$res) {
            $this->close();
            throw new \Exception('Unable to read data from go server：' . socket_strerror(socket_last_error()) . PHP_EOL);
            return false;
        }

        return json_decode($res, true);
    }

    public function close() {
        if(is_resource($this->socket)) {
            socket_write($this->socket, 'EOF');
            socket_close($this->socket);
        }
    }

    public function __destruct() {
        is_resource($this->socket) ? socket_close($this->socket) : '';
    }
}
