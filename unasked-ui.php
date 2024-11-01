<?php
/*
Plugin Name: Unasked Client
Plugin URI: http://unasked.com/webmasters/wp-plugin
Description: Unasked Client
Version: 1.0
Author: Dionylon Briones
Author URI: http://unasked.com/webmasters/wp-plugin
*/

/*  Copyright 2009 Unasked.com 
**
**  This program is free software; you can redistribute it and/or modify
**  it under the terms of the GNU General Public License as published by
**  the Free Software Foundation; either version 2 of the License, or
**  (at your option) any later version.
**
**  This program is distributed in the hope that it will be useful,
**  but WITHOUT ANY WARRANTY; without even the implied warranty of
**  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
**  GNU General Public License for more details.
**
**  You should have received a copy of the GNU General Public License
**  along with this program; if not, write to the Free Software
**  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once 'unasked.php';

class UnaskedUI {

    public $blog_url = '';
    protected $app_url = '';
    protected $request_url = '';
    protected $developer_key = '';
    protected $client;
    

	public function __construct() {
        $this->blog_url = get_bloginfo('siteurl');
        $this->app_url = $this->blog_url.'/'.PLUGINDIR.'/unasked/';
        $this->request_url = $this->app_url.'post.php';
        $this->host = 'http://unasked.com/api';
        $this->developer_key = $this->getOption('developer_key');

        $this->client = new Unasked($this->host, $this->developer_key, $this->blog_url);

        add_action('admin_menu',   array('UnaskedUI', 'admin_menu'));

        if ($this->getOption('connected')) {
            add_action("init", array('UnaskedUI', 'init_jquery'));
            add_action("wp_head", array('UnaskedUI', 'init_head'));
            add_filter('the_content',array('UnaskedUI', 'postQuestion'));
        }
	}

    public function init_jquery() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-form');
    }

    public function init_head() {
        global $UnaskedUI;
        
        $postid = $UnaskedUI->getPostId();
        $posturl = get_permalink($postid);

        $user_auth = (isset($_COOKIE['unasked-user-key']) && !empty($_COOKIE['unasked-user-key'])) ? "true" : "false";
        
        print <<<EOD
        <script type="text/javascript">
        var REQUEST_URL = '{$UnaskedUI->request_url}';
        var POST_ID = {$postid};
        var UNASKED_QUESTION = '';
        var UNASKED_USER_AUTH = {$user_auth};
        var UNASKED_HOST = '{$UnaskedUI->host}';
        var UNASKED_BLOG_URL = '{$UnaskedUI->blog_url}';
        var UNASKED_DEVELOPER_KEY = '{$UnaskedUI->developer_key}';
        var POST_URL = '{$posturl}';

        </script>
        <script type="text/javascript" src="{$UnaskedUI->app_url}unasked.js"></script>
        <link rel="stylesheet" href="{$UnaskedUI->app_url}unasked.css" type="text/css" media="screen" />
EOD;
    }

    private function setOption($key, $value) {
        update_option('unasked_'.$key, $value);
    }

    private function getOption($key) {
        return get_option('unasked_'.$key);
    }

	public function admin_menu() {
		add_options_page('UnAsked.com Client', 'UnAsked.com Client', 8, __FILE__, array('UnaskedUI', 'ui_options'));	
	}

    public function getPostId() {
        global $post;
        return $post->ID ? $post->ID : 0;
    }

    public function loginForm() {
        return <<<EOD
        
        <div id="unasked-login" style="display:none">
            <h3>
                <a title="Close" href="#" class="close-button"><img alt="Close" border=1 src="{$this->app_url}close.gif"></a>
                Login
            </h3>

            <div style="margin-bottom:10px">
                You must be a registered user to ask questions. If you are not registered, <a href="#" class="unasked-register-link">Register Now</a>. Otherwise, please login.
            </div>
            <div class="message"></div>
            <form name="post" action="{$this->request_url}" method="post" id="unasked-login-form">
                <fieldset>
                <label for="email">Email</label>
                <input type="text" name="email" value=""/>
                <label for="password">Password</label>
                <input type="password" name="password" value="" maxlength="20"/>
                <br/>
                <input type="submit" class="submit" value="Login">
                <input type="hidden" name="act" value="login">
                <input type="hidden" name="host" value="{$this->host}"/>
                <input type="hidden" name="blog_url" value="{$this->blog_url}"/>
                <input type="hidden" name="developer_key" value="{$this->developer_key}"/>
                <span style="float:right;">
                <span style="font-size:12px !important">Powered by</span>
                <a href='http://unasked.com' title='Ask A Question'><img border=0 alt='Ask A Question' style="vertical-align: middle" src='{$this->app_url}unasked-logo.png'/></a>
                </span>

                </fieldset>
            </form>
            <a class="unasked-register-link" href="#">Not Registered</a>
        </div>
EOD;
    }

    public function registerForm() {
        $p = parse_url($this->blog_url);
        $url = $p['host'];
        return <<<EOD
        <div id="unasked-register" style="display:none">
            <h3>
                <a title="Close" href="#" class="close-button"><img alt="Close" border=1 src="{$this->app_url}close.gif"></a>
                Register
            </h3>
            <div style="margin-bottom:10px">
            Complete the form below to register with <a href="$this->blog_url">$url</a>. Registering an account will allow you to ask questions on any site powered by UnAsked.com
            </div>
            <div class="message"></div>
            <form name="post" action="{$this->request_url}" method="post" id="unasked-register-form">
                <fieldset>
                <label for="name">Name</label>
                <input type="text" name="name" value=""/>
 
                <label for="email">Email</label>
                <input type="text" name="email" value=""/>

                <label for="password">Password</label>
                <input type="password" name="password" value="" maxlength="20"/>

                <label for="password2">Confirm Password</label>
                <input type="password" name="password2" value="" maxlength="20"/>

                <br/>
                <input class="submit" type="submit" value="Submit">
                <input type="hidden" name="act" value="register">
                <input type="hidden" name="host" value="{$this->host}"/>
                <input type="hidden" name="blog_url" value="{$this->blog_url}"/>
                <input type="hidden" name="developer_key" value="{$this->developer_key}"/>

                <span style="float: right;">
                <span style="font-size:12px !important">Powered by</span>
                <a href='http://unasked.com' title='Ask A Question'><img border=0 alt='Ask A Question' style="vertical-align: middle" src='{$this->app_url}unasked-logo.png'/></a>
                </span>


                </fieldset>
            </form>
        </div>
EOD;

    }

    public function questionForm() {
        $post_url = get_permalink($this->getPostId());
        $rand = rand();
        return <<<EOD
        <div id="unasked-question" style="">

	    <div class="unasked-api-header">
               <div id="unasked-user-info" style="width: 65%; float: right; text-align:right; padding: 7px 0 0 0">
               </div>

              <h3 style="width: 30%; float: left; font-size: 19px">
                Ask a question
              </h3>
            
   	    <div style="clear:both"></div>
            <div class="message"></div>
	    </div>


            <form action="{$this->request_url}" method="post" id="unasked-question-form">
                <fieldset>
                <label for="unasked-question-text">Question: </label>
                <input name="unasked-question" id="unasked-question-text" type="text" value="" size="255" maxlength="255">
                <span class="question-captcha-container">
                <img src="{$this->app_url}create-image.php?{$rand}" class="captcha_image" style="margin:0px;"/>
                <input id="captcha-code" name="captcha" maxlength="5" size="5"/>
                </span><br />
                <input class="submit-question" name="unasked-submit-button" type="submit" value="Submit">
                <input type="hidden" name="act" value="post-question">
                <input type="hidden" name="host" value="{$this->host}"/>
                <input type="hidden" name="blog_url" value="{$this->blog_url}"/>
                <input type="hidden" name="developer_key" value="{$this->developer_key}"/>
                <input type="hidden" name="post_url" value="{$post_url}"/>

                <span style="float: right;">
                <span style="font-size:12px !important">Powered by</span>
                <a href='http://unasked.com' title='Ask A Question'><img border=0 alt='Ask A Question' style="vertical-align: middle" src='{$this->app_url}unasked-logo.png'/></a>
                </span>
                </fieldset>
            </form>
        </div>
EOD;
    }

    public function answerForm() {
        $post_url = get_permalink($this->getPostId());
        return <<<EOD
        <div id="unasked-answer" style="display:none">
            <h3>[<a href="#" class="close-button">x</a>] Post answer</h3>
            <div class="message"></div>
            <form action="{$this->request_url}" method="post" id="unasked-answer-form">
                <fieldset>
                <label for="answer">* Answer:</label><br/>
                <textarea name="answer" id="answer" cols="46" rows="5"></textarea><br/>
                
                <label for="email">* Email:</label><br/>
                <input name="email" id="email" type="text" value="" maxlength="40">
                <div class='answer-captcha-container'>
                </div>

                <input class="submit" name="unasked-submit-button" type="submit" value="Submit Answer">
                <input type="hidden" name="act" value="post-answer"/>
                <input type="hidden" name="question_id" value=""/>
                <input type="hidden" name="parent_answer" value=""/>
                <input type="hidden" name="host" value="{$this->host}"/>
                <input type="hidden" name="blog_url" value="{$this->blog_url}"/>
                <input type="hidden" name="developer_key" value="{$this->developer_key}"/>
                <input type="hidden" name="post_url" value="{$post_url}"/>


                </fieldset>
            </form>
        </div>
EOD;
    }

    public function postQuestion($content) {
        global $UnaskedUI;
        
        if (is_post()) {
            $post_id = $UnaskedUI->getPostId();
            $form = $UnaskedUI->loginForm();
            return <<<EOT
            {$content}
            <div style="clear:both"></div>
            <div class="questions">
            {$UnaskedUI->questionForm()}
            {$UnaskedUI->answerForm()}
            {$UnaskedUI->registerForm()}
            {$form}

            <ul id="unasked-question-list"></ul>
            <script>
                displayUserInfo();
                listQuestions({$post_id}, 1, 10)
            </script>
            </div>
EOT;
        } else {
            return $content;
        }
    }

	public function ui_options() {

		global $UnaskedUI;



        $UnaskedUI->setOption('connected', !$xml['error']);

        if (isset($_POST['submit'])) {
            $UnaskedUI->setOption('developer_key', $_POST['developer_key']);
            $UnaskedUI->client = new Unasked($UnaskedUI->host, $_POST['developer_key'], $UnaskedUI->blog_url);
        }

        $xml = $UnaskedUI->client->echoString('Connected.');

        if (!isset($xml['error'])) {
            $xml['error'] = 1;
            $xml['message'] = <<<EOT
<strong>Failed to connect to unasked.</strong>
<p>
Please check the following:
<ul>
<li>1. <a href="http://unasked.com/member/login">Login</a> to unasked.com</li>
<li>2. On your profile page, please make sure your website ({$UnaskedUI->blog_url}) is added to the <strong>Website URL</strong> field.</li>
<li>3. Please check if you have the correct developer key at the bottom of your profile page</li>
</ul>
</p>
EOT;
        }

		?>
		<div class="wrap">
			<h2>Unasked.com Client Options</h2>
			<div id="message" class="updated fade">
			<?php print $xml['message']; ?>
			</div>

			<form action="" method="POST">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label>Developer key:</label></th>
						<td><input type="text" name="developer_key" value="<?php print $UnaskedUI->getOption('developer_key')?>" size="50" maxlength="50"/></td>
					</tr>
				</tbody>
				</table>
				<p class="submit">
				<input type="submit" value="Save" name="submit">
				</p>

		</form>
    <?php
	}
}

$UnaskedUI = new UnaskedUI();
?>
