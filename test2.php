<?php

class CustomOpenAPIClient {
    private $apiKey;
    private $apiUrl;
    private $assistantId;

    public function __construct($apiKey, $assistantId, $apiUrl = 'https://api.openai.com/v1') {
        $this->apiKey       = $apiKey;
        $this->apiUrl       = $apiUrl;
        $this->assistantId  = $assistantId;
    }

    public function request($message) {
        $create_thread_url = "{$this->apiUrl}/threads";
        $thread_res = $this->createThread($this->apiKey, $create_thread_url);
        if ($thread_res) {
            $thread_id = $thread_res;
            // Step 2: Add a Message to thread using endpoint v1/threads/$thread_id/messages
            $add_message_to_thread_url = "{$this->apiUrl}/threads/{$thread_id}/messages";
            $add_msg_res = $this->addMessageToThread($this->apiKey, $message, $add_message_to_thread_url);
            if ($add_msg_res) {
                // Step 3: Run the Assistant on the Thread
                $data_ass = ["assistant_id" => "$this->assistantId"];
                $run_url = "{$this->apiUrl}/threads/{$thread_id}/runs";
                $run_id = $this->runMessageThread($this->apiKey, $data_ass, $run_url);
                if ($run_id) {
                    // Step 4: run steps 
                    $run_steps_url = "{$this->apiUrl}/threads/{$thread_id}/runs/{$run_id}/steps";
                    $this->getRunSteps($this->apiKey, $run_steps_url);           
                    // Step 5: status of run
                    $runs_sts_url = "{$this->apiUrl}/threads/{$thread_id}/runs/{$run_id}";      
                    $status = "";
                    while(true) {
                        $sts_chk = $this->getRunStatus($this->apiKey, $runs_sts_url);
                        $sts_chk = json_decode($sts_chk, true);
                        if ($sts_chk['status'] == 'in_progress') {
                            sleep(1);
                            continue;
                        }
                        $status = $sts_chk['status'];
                        break;
                    }
                    if ($status == "completed") {
                        $ass_res_rul = "{$this->apiUrl}/threads/{$thread_id}/messages";
                        $res = $this->getAssResponseMessages($this->apiKey, $ass_res_rul);
                        if ($res) {
                            $data_d = json_decode($res, true);
                            $html = $data_d['data'][0]['content'][0]['text']['value'];
                            return json_encode(['response' => $html, 'status' => $sts_chk]);
                        }
                        else {
                            return json_encode(['response' => 'Request failed.']);
                        }
                    }
                    else {
                        return json_encode(['response' => 'Request failed.']);
                    }
                }
            }    
        }
    }

    private function createThread($api_key, $url) {
        $response = $this->curlAPIPost($api_key, $url);
        if ($response) {
            $thread_data = json_decode($response, true);
            if (isset($thread_data['id'])) {
                $thread_id = $thread_data['id'];
                return $thread_id;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    private function addMessageToThread($api_key, $message, $create_thread_url) {
        $add_thread_data = ["role" => "user", "content" => $message];
        $response = $this->curlAPIPost($api_key, $create_thread_url, $add_thread_data);
        if ($response) {
            $msg_data = json_decode($response, true);
            if (isset($msg_data['id'])) {
                return $msg_data['id'];
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }    
    }

    private function runMessageThread($api_key, $message, $run_thread_url) {
        $response = $this->curlAPIPost($api_key, $run_thread_url, $message);
        if ($response) {
            $run_data = json_decode($response, true);
            if (isset($run_data['id'])) {
                return $run_data['id'];
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    private function getRunSteps($api_key, $url) {
        $response = $this->getCurlCall($api_key, $url);
        return $response;
    }

    private function getRunStatus($api_key, $url) {
        $response = $this->getCurlCall($api_key, $url);
        return $response;
    }

    private function getAssResponseMessages($api_key, $url) {
        $response = $this->getCurlCall($api_key, $url);
        return $response;
    }

    private function curlAPIPost($api_key, $url, $data = '') {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'OpenAI-Beta: assistants=v2',
        ];
        $curl = curl_init($url);
        if ($data != '') {
            $json_data = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);  
        if ($err) {
            return false;
        }
        else {
            return $response;
        }
    }

    private function getCurlCall($api_key, $url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'GET',
            CURLOPT_HTTPHEADER      => array(
                'OpenAI-Beta: assistants=v2',
                'Authorization: Bearer ' . $api_key,
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);  
        if ($err) {
            return false;
        }
        else {
            return $response;
        }   
    }
};

?>