<?php
/*
Description: Unasked Client
Author: Dionylon Briones <lon.briones@gmail.com>
*/

class Unasked {

    protected $blog_url;
    protected $api_url;
    protected $debug = false;
    public $error = '';

    public function __construct($api_url, $developer_key, $developer_url) {
        $this->api_url = $api_url.'?'.'developer_key='.$developer_key.'&developer_url='.$developer_url;
        if ($this->debug) {
            print $this->api_url;
        }
    }

    public function submitQuestionBySitePost($question_text, $blog_url, $user_key) {
        return $this->query($this->api_url.'&function=submitQuestionBySitePost&question_text='.urlencode($question_text).'&user_key='.$user_key.'&blog_url='.urlencode($blog_url));
    }

    public function submitAnswer($question_id, $answer_text, $parent_answer, $user_key) {
        return $this->query($this->api_url.'&function=submitAnswer&question_id='.$question_id.'&answer_text='.urlencode($answer_text).'&parent_answer='.$parent_answer.'&user_key='.$user_key);
    }

    public function getQuestionsBySitePost($blog_url, $perpage, $pageno) {
        return $this->query($this->api_url.'&function=getQuestionsBySitePost&blog_url='.$blog_url.'&perpage='.$perpage.'&pageno='.$pageno);
    }

    public function getAnswersByQuestion($question_id) {
        return $this->query($this->api_url.'&function=getAnswersByQuestion&question_id='.$question_id);
    }

    public function echoString($string) {
        return $this->query($this->api_url.'&function=echoString&string='.$string);
    }

    public function login($user_key) {
        return $this->query($this->api_url.'&function=authenticateUser&user_key='.$user_key);
    }

    public function registerUser($email, $username, $password) {
        $username = urlencode($username);
        $password = urlencode($password);
        return $this->query($this->api_url.'&function=registerUser&email='.$email.'&username='.$username.'&password='.$password);
    }

    public function query($url) {
        try {
            $ret = new SimpleXMLElement($url, LIBXML_NOERROR, TRUE);
            return (array)$ret->children();
        } catch(Exception $e) {
            $debug_info = $this->debug ? '<br/>'.$e->getMessage().'<br/>'.$url : '';
            $this->error = "<br/><span class='error'>Failed to retrieve data from <a href='http://unasked.com'>Unasked.com</a>$debug_info</span>";
        }
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }
}
?>
