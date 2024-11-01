jQuery(document).ready(function(){
    jQuery('#unasked-post-question-link').click(function(){
        return false;
    });

    jQuery('.unasked-register-link').each(function(){
        jQuery(this).click(function(){
            jQuery('#unasked-register').show();
            jQuery('#unasked-register .message').html('');
            jQuery('#unasked-login').hide();
            return false;
        });
    });

    jQuery('#unasked-register-form').ajaxForm({
        dataType: 'json',
        beforeSubmit: function(formData, jqForm, options){
            jQuery('#unasked-register .message').html('');
            var name = jQuery('#unasked-register-form input[@name="name"]').val();
            var email = jQuery('#unasked-register-form input[@name="email"]').val();
            var password = jQuery('#unasked-register-form input[@name="password"]').val();
            var password2 = jQuery('#unasked-register-form input[@name="password2"]').val();
            
            var error = '';
            if (!name) {
                error = error + 'Name is empty<br/>';
            }

            if (!email) {
                error = error + 'Email is empty<br/>';
            }

            if (!password) {
                error = error + 'Password is empty<br/>';
            } else if (password != password2) {
                error = error + "Passwords don't match<br/>";
            }
            if (error) {
                jQuery('#unasked-register .message').html(error).fadeIn();
                return false;
            }
            return true;

        },
        success: function(ret, statusText) {
            var message = '';
            if(ret['error'] == 0) {
                jQuery('#unasked-register').hide();
                jQuery('#unasked-register .message').html('');
                message = 'Your registration was successful, please login using the form below.';
                jQuery('#unasked-login .message').html(message).fadeIn();
                jQuery('#unasked-login').show();
            } else {
                message = ret['message'];
            }
            jQuery('#unasked-register .message').html(message).fadeIn();
        }
    });

    jQuery('#unasked-login .close-button').click(function(){
        jQuery('#unasked-login').hide();
        jQuery('#unasked-question').show();
        return false;
    });

    jQuery('#unasked-register .close-button').click(function(){
        jQuery('#unasked-register').hide();
        jQuery('#unasked-question').show();
        return false;
    });


    
    jQuery('#unasked-login-form').ajaxForm({
        dataType: 'json',
        beforeSubmit: function(formData, jqForm, options){
            if (formData[0]['value'] == '' || formData[1]['value'] == '') {
                jQuery('#unasked-login .message').html('Invalid username or password');
                return false;
            }
        },
        success: function(ret, statusText) {
            var message = '';
            if (ret['error'] == 0) {
                message = "You're logged in Unasked";
                jQuery('#unasked-login').hide();
                displayUserInfo();
                UNASKED_USER_AUTH = true;
                jQuery('#unasked-question').show();
            } else {
                message = ret['message'];
            }
            jQuery('#unasked-login .message').html(message);
        }
    });

    jQuery('#unasked-question-form').ajaxForm({
        dataType: 'json',
        beforeSubmit: function(formData, jqForm, options){
            UNASKED_QUESTION = formData[0]['value'];
            if (UNASKED_QUESTION == '') {
                jQuery('#unasked-question .message').html('Please enter a question');
                jQuery('input[@name="unasked-question"]').focus();
                
                return false;
            } else {
                var question_start = ['am','are','can','could','did','do','does','how','if','is','should','what','when','where','which','will','who','why'];
                var question_txt = UNASKED_QUESTION.toLowerCase();
                var result = false;
                for (var i in question_start) {
                    if (result==false) {
                        result = ( question_txt.indexOf( question_start[i]+' ' ) == 0 ) ? true : false ;
                    }
                }
                if (result==false) {
                    jQuery('#unasked-question .message').html('Question should begin with Am, Are, Can, Could, Did, Do, Does, How, If, Is, Should, What, When, Where, Which, Will, Who, or Why.').hide().show('fast');
                    jQuery('input[@name="unasked-question"]').focus();
                }

                if (result) {
                    if (!UNASKED_USER_AUTH) {
                        jQuery('#unasked-login').show();
                        jQuery('#unasked-question').hide();
                        return false;
                    } else {
                        UNASKED_QUESTION = '';
                        return true;
                    }
                } else {
                    refreshCaptcha();
                    return false;
                }
            }
        },
        success: function(responseText, statusText) {
            if (responseText['error'] == 0) {
                jQuery('#unasked-question .message').html('Thank you for submitting your question');
                jQuery('input[@name="unasked-question"]').val('');
                refreshCaptcha();
                listQuestions(POST_ID, 1, 10);
            } else {
                jQuery('#unasked-question .message').html(responseText['message']);
                refreshCaptcha();
            }
            jQuery('input[@name="captcha"]').val('');
        }
    });
});

function userAuth() {
    jQuery.ajax({
        url: REQUEST_URL, 
        type: 'POST',
        async: false,
        data: {act:'user-logged-in'},
        success: function(msg) {
            if (msg) {
                UNASKED_USER_AUTH = true;
            }
        }
    });
}

function setLogoutLink() {
    jQuery('#unasked-logout').click(function() {
        jQuery.ajax({
            url: REQUEST_URL, 
            type: 'POST',
            data: {act:'logout', host: UNASKED_HOST, blog_url: UNASKED_BLOG_URL, developer_key: UNASKED_DEVELOPER_KEY},
            success: function(msg) {
                if (msg) {
                    jQuery('#unasked-user-info').html(jQuery(msg));
                    jQuery('#unasked-question').show();
                    jQuery('#unasked-question .message').html('');
                    jQuery('#unasked-login .message').html('');
                    UNASKED_USER_AUTH = false;
                }
            }
        });
        return false;
    });
}

function listAnswers(question_id) {
    var answers_list = jQuery('#question-'+question_id).children('.answers-list')
    if (answers_list.html() == '') {
        jQuery.ajax({
            url: REQUEST_URL, 
            type: 'POST',
            async: false,
            data: {act:'list-answers', question_id: question_id, host: UNASKED_HOST, blog_url: UNASKED_BLOG_URL, developer_key: UNASKED_DEVELOPER_KEY},
            success: function(msg) {
                if (msg) {
                    answers_list.html(jQuery(msg));

                    jQuery('#question-'+question_id+' .answer-link').each(function(){
                        jQuery(this).click(function(){
                            var parent_answer = this.hash.replace('#', '');
                            var form_container = jQuery(this).parent().children('.answer-form-container');
                            showAnswerForm(form_container, question_id, parent_answer);
                            return false;

                        });
                    });
                    answers_list.show('slow');
                    
                }
            }
        });
    } else {
        answers_list.toggle('slow');
    }
}

function listQuestions(postid, page, perpage) {
    jQuery.ajax({
        url: REQUEST_URL, 
        type: 'POST',
        async: false,
        dataType: 'json',
        data: {act:'list-question', post_url: POST_URL, perpage: perpage, page: page, host: UNASKED_HOST, blog_url: UNASKED_BLOG_URL, developer_key: UNASKED_DEVELOPER_KEY},
        success: function(ret) {
            jQuery('#unasked-question-list').html(ret['message']);
            jQuery('#unasked-question-list a.answer-link').each(function(){
                var question_id = this.hash.replace('#', '');
                jQuery(this).prev().prev('.show-answers-link').click(function() {
                    listAnswers(question_id);
                    return false;
                });
                jQuery(this).click(function(){
                    var form_container = jQuery(this).parent().children('.answer-form-container');
                    
                    if (answers_list = jQuery('#question-'+question_id).children('.answers-list').html() == '') {
                        listAnswers(question_id);
                    }
                    showAnswerForm(form_container, question_id);
                    return false;
                });
            });
        }
    });
}


function showAnswerForm(container, question_id, parent_answer) {

    if (container[0].innerHTML == '') {
        container[0].innerHTML = jQuery('#unasked-answer')[0].innerHTML;
    }

    form = container.children('form');
    var form_id = 'unasked-answer-form-'+question_id;

    if (parent_answer != undefined) {
        form_id += '-'+parent_answer;
    }

    form[0].id = form_id;

    if (UNASKED_USER_AUTH) {
        jQuery('#'+form_id+' input[@name="email"]').hide();
        jQuery('#'+form_id+' input[@name="email"]').prev().hide();
        jQuery('#'+form_id+' input[@name="email"]').prev().prev().hide();
    } else {
        jQuery('#'+form_id+' input[@name="email"]').show();
        jQuery('#'+form_id+' input[@name="email"]').prev().show();
        jQuery('#'+form_id+' input[@name="email"]').prev().prev().show();
    }

    jQuery('#'+form_id+' .answer-captcha-container').html(jQuery('.question-captcha-container').html());
    
    container.fadeIn('slow');

    jQuery(container.children('h3').children()[0]).click(function() {
        container.hide('slow');
        return false;
    });

    jQuery('#'+form_id+' input[@name="parent_answer"]').val(parent_answer);
    jQuery('#'+form_id+' input[@name="question_id"]').val(question_id);
    jQuery(form).ajaxForm({
        dataType: 'json',
        beforeSubmit: function(formData, jqForm, options){
            var answer = formData[0]['value'];
            var email = formData[1]['value'];
            var check_email = jQuery('#'+form_id+' input[@name="email"]').css('display') != 'none';
            var error = '';
            if (answer == '') {
                jQuery('#'+form_id+' input[@name="answer"]').focus();
                error = 'Please enter your answer';
            } else if(email == '' && check_email) {
                jQuery('#'+form_id+' input[@id="email"]').focus();
                error = 'Please enter your email';
            } else if(/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(email) == false && check_email) {
                jQuery('#'+form_id+' input[@id="email"]').focus();
                error = 'Invalid email address';
            }
            if (error) {
                container.children('.message').html(error);
                return false;
            } else {
                //container.fadeOut();
                return true;
            }
        },
        success: function(responseText, statusText) {
            if (responseText['error'] == 0) {
                container.children('.message').html(responseText['message']);
                listQuestions(POST_ID, 1, 10);
                listAnswers(question_id);
            } else {
                container.children('.message').html(responseText['message']);
            }
            refreshCaptcha();
        }
    });

}

function refreshCaptcha() {
   jQuery('.captcha_image').each(function(){
        this.src = this.src.split('?')[0]+'?'+Math.random();
   });
}

function displayUserInfo() {
    jQuery.ajax({
        url: REQUEST_URL, 
        type: 'POST',
        data: {act:'user-info', host: UNASKED_HOST, blog_url: UNASKED_BLOG_URL, developer_key: UNASKED_DEVELOPER_KEY},
        success: function(msg) {
            if (msg) {
                jQuery('#unasked-user-info').html(jQuery(msg));
                setLogoutLink();
            }
        }
    });
}
