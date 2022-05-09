<?php

class mp3
{
    var $str;
    var $time;
    var $frames;

    function mp3($path = "")
    {
        if ($path != "")
        {
            $this->str = file_get_contents($path);
        }
    }

    function mergeBehind($mp3)
    {
        $this->str .= $mp3->str;
    }

    function getIdvEnd()
    {
        $strlen = strlen($this->str);
        $str = substr($this->str, ($strlen - 128));
        $str1 = substr($str, 0, 3);
        if (strtolower($str1) == strtolower('TAG'))
        {
            return $str;
        }
        else
        {
            return false;
        }
    }

    function getStart()
    {
        $strlen = strlen($this->str);
        for ($i = 0;$i < $strlen;$i++)
        {
            $v = substr($this->str, $i, 1);
            $value = ord($v);
            if ($value == 255)
            {
                return $i;
            }
        }
    }

    function striptags()
    {
        $newStr = '';
        $s = $start = $this->getStart();
        if ($s === false)
        {
            return false;
        }
        else
        {
            $this->str = substr($this->str, $start);
        }

        $end = $this->getIdvEnd();
        if ($end !== false)
        {
            $this->str = substr($this->str, 0, (strlen($this->str) - 129));
        }
    }

    function error($msg)
    {
        die(array("success"=>false, "error"=>"audio_file_error", "message"=> $msg));
    }

    function output($path)
    {

        if (ob_get_contents()) $this->error('Some data has already been output, can\'t send mp3 file');
        if (php_sapi_name() != 'cli')
        {
            //We send to a browser
            header('Content-Type: audio/mpeg');
            if (headers_sent()) $this->error('Some data has already been output to browser, can\'t send mp3 file');
            header('Content-Length: ' . strlen($this->str));
            header('Content-Disposition: inline; filename="' . $path . '"');
            header('Cache-Control: no-cache');
            header("Content-Transfer-Encoding: chunked"); 
        }
        echo $this->str;
        return '';
    }
}

//end of mp3 module

$stops = "audiocloud933333333331/stops/";

$announcements = "audiocloud933333333331/announcements/";

$etc = "audiocloud933333333331/etc/";

function get_line_path($transport, $line){
    $bus = "audiocloud933333333331/lines/bus/";
    $tram = "audiocloud933333333331/lines/tram/";
    switch ($transport) {
        case 'bus':
            if(!file_exists($bus.$line.".mp3")){
                die(json_encode(array("success"=>false, "error"=>"no_file", "message"=> "line audiofile not found")));
            }else{
                return $bus.$line.".mp3";
            }
            break;
        case 'tram':
            if(!file_exists($tram.$line.".mp3")){
                die(json_encode(array("success"=>false, "error"=>"no_file", "message"=> "line audiofile not found")));
            }else{
                return $tram.$line.".mp3";
            }
            break;
        default:
            die(json_encode(array("success"=>false, "error"=>"unsupported_transport", "message"=> "this transport type is not supported yet")));
            break;
    }
}




$data = $_GET;

if(!isset($data)){
    die(json_encode(array("success"=>false, "error"=>"no_input", "message"=> "no input")));
}else{
    if(!isset($data["type"])){
        die(json_encode(array("success"=>false, "error"=>"not_enough_data", "message"=> "not enough data")));
    }else{
        switch ($data["type"]) {
            //LINE DIRECTION MODE
            case 'line_dir':
                if(isset($data["line"]) && isset($data["direction"]) && isset($data["transport"])){
                    $linepath = get_line_path($data["transport"], $data["line"]);
                    if(!file_exists($stops.$data["direction"].".mp3")){
                        die(json_encode(array("success"=>false, "error"=>"no_file", "message"=> "direction audiofile not found")));
                    }
                    //line
                    $mp3 = new mp3($linepath);
                    $mp3->striptags();
                    //direction
                    $cas_mp3equivalent = new mp3($stops.$data["direction"].".mp3");
                    $mp3->mergeBehind($cas_mp3equivalent);
                    $mp3->striptags();
                    //output
                    $mp3->output($data["transport"].'_'.$data["line"].'_'.$data["direction"].'.mp3');
                }else{
                    die(json_encode(array("success"=>false, "error"=>"not_enough_data", "message"=> "not enough data")));
                }
                break;
            //ANNOUNCEMENT MODE
            case 'announcement':
                if(isset($data["name"])){
                    if(!file_exists($announcements.$data["name"].".mp3")){
                        die(json_encode(array("success"=>false, "error"=>"no_file", "message"=> "announcement audiofile not found")));
                    }
                    //line
                    $mp3 = new mp3($announcements.$data["name"].".mp3");
                    $mp3->striptags();
                    //output
                    $mp3->output("announcement_".$data["name"].'.mp3');
                }else{
                    die(json_encode(array("success"=>false, "error"=>"not_enough_data", "message"=> "not enough data")));
                }
                break;
            //STOPS MODE
            case 'stops':
                if(isset($data["stop"]) || isset($data["stop2"])){
                    $s1e = false;
                    $s2e = false;
                    if(file_exists($stops.$data["stop"].".mp3")){
                        $s1e = true;
                    }
                    if(file_exists($stops.$data["stop2"].".mp3")){
                        $s2e = true;
                    }
                    if(!$s1e && !$s2e){
                        die(json_encode(array("success"=>false, "error"=>"not_found", "message"=> "stop & stop2 audiofiles not found")));
                    }
                    if($s1e && $s2e){
                        //s1
                        $mp3 = new mp3($stops.$data["stop"].".mp3");
                        $mp3->striptags();
                        //next
                        $next = new mp3($etc."next.mp3");
                        $mp3->mergeBehind($next);
                        $mp3->striptags();
                        //s2
                        $s2 = new mp3($stops.$data["stop2"].".mp3");
                        $mp3->mergeBehind($s2);
                        $mp3->striptags();
                        //output
                        $mp3->output($stops.$data["stop"].'_'.$stops.$data["stop2"].'.mp3');
                    }
                    if($s1e && !$s2e){
                        if(isset($data['is_end'])){
                            if($data['is_end'] == "yes"){
                                //s1
                                $mp3 = new mp3($stops.$data["stop"].".mp3");
                                $mp3->striptags();
                                //end
                                $next = new mp3($etc."last.mp3");
                                $mp3->mergeBehind($next);
                                $mp3->striptags();
                                $mp3->output($stops.$data["stop"].'_last.mp3');
                            }elseif($data['is_end'] == "no"){
                                //s1
                                $mp3 = new mp3($stops.$data["stop"].".mp3");
                                $mp3->striptags();
                                $mp3->output($stops.$data["stop"].'.mp3');
                            }else{
                                die(json_encode(array("success"=>false, "error"=>"invalid_param", "message"=> "is_end can be only yes or no")));
                            }
                        }elseif(isset($data['is_next'])){
                            if($data['is_next'] == "yes"){
                                //s1
                                $mp3 = new mp3($etc."next.mp3");
                                $mp3->striptags();
                                //end
                                $s2 = new mp3($stops.$data["stop"].".mp3");
                                $mp3->mergeBehind($s2);
                                $mp3->striptags();
                                $mp3->output('next_'.$stops.$data["stop"].'.mp3');
                            }elseif($data['is_next'] == "no"){
                                //s1
                                $mp3 = new mp3($stops.$data["stop"].".mp3");
                                $mp3->striptags();
                                $mp3->output($stops.$data["stop"].'.mp3');
                            }else{
                                die(json_encode(array("success"=>false, "error"=>"invalid_param", "message"=> "is_next can be only yes or no")));
                            }
                        }else{
                            //s1
                            $mp3 = new mp3($stops.$data["stop"].".mp3");
                            $mp3->striptags();
                            $mp3->output($stops.$data["stop"].'_last.mp3');
                        }
                    }
                    if(!$s1e && $s2e){
                        //s1
                        $mp3 = new mp3($etc."next.mp3");
                        $mp3->striptags();
                        //end
                        $s2 = new mp3($stops.$data["stop2"].".mp3");
                        $mp3->mergeBehind($s2);
                        $mp3->striptags();
                        $mp3->output('next_'.$stops.$data["stop2"].'.mp3');
                    }
                }else{
                    die(json_encode(array("success"=>false, "error"=>"not_enough_data", "message"=> "not enough data")));
                }
                break;
            default:
                die(json_encode(array("success"=>false, "error"=>"invalid_type", "message"=> "invalid type")));
                break;
        }
    }
}