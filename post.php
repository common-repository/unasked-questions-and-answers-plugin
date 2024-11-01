<?php
session_start();

$ret = array(1, 'Unknown error');

//The developer's site
$blog_url = isset($_REQUEST['blog_url']) ? $_REQUEST['blog_url'] : '';
//Get the domain for the cookie
$url_parts = parse_url($blog_url);
$cookie_domain = $url_parts['host'];

$login_link = <<< EOD
<a id="unasked-login-link" href="#login">Login</a>
<script>
    jQuery("#unasked-login-link").click(function(){ 
        jQuery("#unasked-question").hide(); 
        jQuery("#unasked-register").hide(); 
        jQuery("#unasked-login").fadeIn("slow"); 
        jQuery("#unasked-login .message").html("");
});
</script>;
EOD;

//Die on user-logout
if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'logout') {
    $browser_type = $_SERVER['HTTP_USER_AGENT'];
    $parts = explode(" ",$browser_type);
    $ie = array_search("MSIE",$parts);
                
    if($ie) {
        setcookie("unasked-user-key", "", time()+20000);
        setcookie("unasked-user-name", "", time()+20000);
    } else {
        setcookie("unasked-user-key", "", time()-20000, "/", $cookie_domain);
        setcookie("unasked-user-name", "", time()-20000, "/", $cookie_domain);
    }

    die($login_link);
}

$developer_key = isset($_REQUEST['developer_key']) ? $_REQUEST['developer_key'] : 0;
$post_url = isset($_REQUEST['post_url']) ? $_REQUEST['post_url'] : '';
$host = isset($_REQUEST['host']) ? $_REQUEST['host'] : '';

if (!$developer_key || !$host || !$blog_url) {
    die('Invalid parameters');
}

require('unasked.php');
$Unasked = new Unasked($host, $developer_key, $blog_url);

if (isset($_REQUEST['act'])) {
    
    $blog_url = isset($_REQUEST['blog_url']) ? $_REQUEST['blog_url'] : '';

    switch($_REQUEST['act']) {
        case 'register':
            $email = $_REQUEST['email'];
            
            $regex = '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';
            if (preg_match($regex, trim($email), $matches) == false) { 
                die('Invalid email');
            }

            $name = $_REQUEST['name'];
            $password = $_REQUEST['password'];
            $password2 = $_REQUEST['password'];
            
            $xml = '';
            
            if ($email && $name && $password && $password2) {
                if ($password != $password2) {
                    $ret = array(1, "Password doesn't match");
                } else {
                    $xml = $Unasked->registerUser($email, $name, $password);
                    $ret = handleUnaskedError($Unasked, $xml);
                }
            }
            break;
        case 'login':
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
            $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
            $email = md5($email, false);

            $password = md5($password, false);
            $user_key = md5($email.$password);

            $xml = $Unasked->login($user_key);
            
            if (isset($xml['error']) && !$xml['error']) {
                setcookie('unasked-user-key', $user_key, time()+36000, "/", $cookie_domain);
                setcookie('unasked-user-name', $xml['username'], time()+36000, "/", $cookie_domain);
                $ret = array($xml['error'], $xml['username']);
            } else {
                $ret = handleUnaskedError($Unasked, $xml);
            }
            break;

        case 'list-question':
            $perpage = $_REQUEST['perpage'];
            $page = $_REQUEST['page'];
            if (!$page) {
                $page = 1;
            }
            
            $results = $Unasked->getQuestionsBySitePost($post_url, $perpage, $page);

            
            $total = isset($results['count']) ? $results['count'] : 0;

            $html = '';
            if ($total) {
                $ret = "<h3>Questions</h3>";
                foreach($results['rows'] as $result) {
                    $result = (array)$result;
                    $link = '<a href="#'.$result['id'].'" class="%s">%s</a>';

                    $html .= '<li class="question-row" id="question-'.$result['id'].'">'.sprintf($link,'show-answers-link', $result['question_text']).'? <span class="create-date">by '.$result['username'].' '.elapsedTime($result['create_date']).'</span> '.sprintf($link,'answer-link', 'Answer').'<div class="answer-form-container" style="display:none"></div><ul class="answers-list"></ul>';
                    
                    $html .= '</li>';
                }
                $html .= pagination($total, $page, $perpage, '<a href="#" onclick="listQuestions(\''.$post_url.'\', %d, '.$perpage.'); return false">%s</a>');
            }
            
            if (isset($results['error'])) {
                $ret = $results['error'] ? array(1, $results['message']) : array(0, addslashes($html));
            } elseif($Unasked->error) {
                $ret = array(1, $Unasked->error);
            } else {
                $ret = array(1, 'Unknown error');
            }


            break;

        case 'list-answers':
            $html = '';
            $question_id = isset($_REQUEST['question_id']) ? $_REQUEST['question_id'] : 0;
            if ($question_id) {
                $answers = $Unasked->getAnswersByQuestion($question_id);
                if (isset($answers['rows'])) {
                    foreach($answers['rows'] as $answer) {
                        $answer = (array)$answer;
                        $link = '<a href="#'.$answer['id'].'" class="%s">%s</a>';
                        $html .= '<li>'.stripslashes($answer['answer_text']).' '.(sprintf($link, 'answer-link', 'Add Comment')).'<div style="display: none;" class="answer-form-container"/></li>';
                        if (isset($answer['comments'])) {
                            $html .= display_comments($answer['comments']);
                        }
                    }
                } else {
                    $html .= '<li><strong>Be the first one to answer this question.</strong></li>';
                }
            }

            $ret = array(0, $html);
            break;

        case 'post-question':
            if (($_REQUEST["captcha"] != $_SESSION["security_code"]) || (empty($_REQUEST["captcha"]) || empty($_SESSION["security_code"])) ) {
                $ret = array(1, 'Invalid security code');
            } elseif (!empty($_COOKIE['unasked-user-key'])) {
                $question = isset($_REQUEST['unasked-question']) ? $_REQUEST['unasked-question'] : '';
                $xml = $Unasked->submitQuestionBySitePost($question, $post_url, $_COOKIE['unasked-user-key']);
                $ret = handleUnaskedError($Unasked, $xml);
            } else {
                $ret = array(1, "Please login");
            }
            
            break;

        case 'post-answer':
            if (($_REQUEST["captcha"] != $_SESSION["security_code"]) || (empty($_REQUEST["captcha"]) || empty($_SESSION["security_code"])) ) {
                die("{error:1, message: \"Invalid security code\"}");
            }

            $question_id = isset($_REQUEST['question_id']) ? $_REQUEST['question_id'] : '';
            $answer = isset($_REQUEST['answer']) ? $_REQUEST['answer'] : '';
            $parent_answer = isset($_REQUEST['parent_answer']) ? $_REQUEST['parent_answer'] : '';

            if (!isset($_COOKIE['unasked-user-key'])) {
                $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
                if (!$email) {
                    die("{error:1, message: \"Invalid email\"}");
                }
                $user_key = md5($email);
            } else {
                $user_key = $_COOKIE['unasked-user-key'];
            }

            $xml = $Unasked->submitAnswer($question_id, $answer, $parent_answer, $user_key);
            $ret = handleUnaskedError($Unasked, $xml);
            break;

        case 'user-info':
            $ret = '';
            if (isset($_COOKIE['unasked-user-key'])) {
                $ret = array(0, '<div id="unasked-user-info">Logged in as '. $_COOKIE['unasked-user-name'].' <a id="unasked-logout" href="#">Logout</a></div>');
            } else {
                $ret = array(1, $login_link);
            }
            break;

        case 'user-logged-in':
            $ret = !$_COOKIE['unasked-user-key'];
            
    }
}

function display_comments($comments) {
    $ret = '';
    if ($comments) {
        $ret .= '<ul>';
        foreach($comments as $rec) {
            $rec = (array)$rec;
            $link = '<a href="#'.(int)$rec['id'].'" class="%s">%s</a>';
            $ret .= '<li>'.stripslashes((string)$rec['answer_text']).' '.(sprintf($link, 'answer-link', 'Add Comment')).'<div style="display: none;" class="answer-form-container"/></li>';
            if (isset($rec['comments'])) {
                $ret .= display_comments($rec['comments']);
            }
        }
        $ret .= '</ul>';
    }
    return $ret;
}

function elapsedTime($event_date) {

    // time units in reference to seconds
    $second = 1;
    $minute = 60 * $second;
    $hour = 60 * $minute;
    $day = 24 * $hour;
    $week = 7 * $day;
    $month = 30.43 * $day;
    $year = 365.25 * $day;
    $time_unit = array(
        array('unit'=>'second', 'value'=>$second),
        array('unit'=>'minute', 'value'=>$minute),
        array('unit'=>'hour', 'value'=>$hour),
        array('unit'=>'day', 'value'=>$day),
        array('unit'=>'week', 'value'=>$week),
        array('unit'=>'month', 'value'=>$month),
        array('unit'=>'year', 'value'=>$year));

    $diff = mktime() - $event_date;

    // determine elapsed time category (minutes, hours...)
    $elapsed_cat = 1;
    if ($diff<$hour) { // minutes elapsed
        $elapsed_cat = 1;
    } elseif (($diff>=$hour)&&($diff<$day)) { // hours elapsed
        $elapsed_cat = 2;
    } elseif (($diff>=$day)&&($diff<$week)) { // days elapsed
        $elapsed_cat = 3;
    } elseif (($diff>=$week)&&($diff<$month)) { // weeks elapsed
        $elapsed_cat = 4;
    } elseif (($diff>=$month)&&($diff<$year)) { // months elapsed
        $elapsed_cat = 5;
    } elseif ($diff>=$year) { // years elapsed
        $elapsed_cat = 6;
    }

    // actual elapsed time computation
    $value1 = (int)($diff/$time_unit[$elapsed_cat]['value']);
    $value2 = (int)(($diff%$time_unit[$elapsed_cat]['value'])/$time_unit[$elapsed_cat-1]['value']);

    // plurals
    if ($value1>1)
    {
        $time_unit[$elapsed_cat]['unit'] = $time_unit[$elapsed_cat]['unit'].'s';
    }
    if ($value2>1)
    {
        $time_unit[$elapsed_cat-1]['unit'] = $time_unit[$elapsed_cat-1]['unit'].'s';
    }

    // lets NOT show seconds and 0 units
    if(($time_unit[$elapsed_cat-1]['unit']=='second')||($value2==0)) {
        $value2 = '';
        $time_unit[$elapsed_cat-1]['unit'] = '';
    }

    return sprintf('%d %s %s %s ago',
        $value1,
        $time_unit[$elapsed_cat]['unit'],
        $value2,
        $time_unit[$elapsed_cat-1]['unit']);
}


function pagination($total, $page, $perpage, $link='') {
 
    // number of pages
    $total_pages = ceil($total/$perpage);

    if($page > $total_pages) {
        $page = $total_pages;
    }
     
    $start_index = ($page - 1) * $perpage;

     
    if($total > $perpage) {
        $end_index = $start_index + $perpage;
    }
    else {
        $end_index = $total;
    }
     
    if($page == $total_pages) {
        if($total > $total_pages * $perpage) {
            $end_index = $total;
        }
    }
     
    if($total > 0) {
        $range_start = $start_index+1;
     
        if ($end_index > $total) {
            $range_end = $total;
        } else {
            $range_end = $end_index;
        }
     
        $range = 'Showing '  . $range_start.' - '.$range_end.' of '.$total;
    }
     
    // link for next page
    if ($page < $total_pages) {
        $next_link = sprintf($link, ($page+1), 'Next ');
    } else {
        $next_link = 'Next';
    }
     
    // link for previous page
    if ($page > 1) {
        $previous_link = sprintf($link, ($page-1), 'Previous');
    } else {
        $previous_link = 'Previous';
    }

    $next_previous_link = ($total > $perpage) ? "$previous_link $next_link" : '';
    
    return '<div class="pagination">'.$range.' '.$next_previous_link.'</div>';

}

function handleUnaskedError($Unasked, $xml) {
    if (isset($xml['error'])) {
        return array($xml['error'], $xml['message']);
    } elseif($Unasked->error) {
        return array(1, $Unasked->error);
    } else {
        return array(1, 'Unknown error');
    }
}

print "{error:{$ret[0]}, message: \"{$ret[1]}\"}";
?>
