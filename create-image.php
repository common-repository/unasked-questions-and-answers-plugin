<?php
//Start the session so we can store what the security code actually is
session_start();

//Send a generated image to the browser 
create_image(); 
exit(); 

function create_image() 
{ 
    //Let's generate a totally random string using md5 
    $md5_hash = md5(rand(0,999)); 
    //We don't need a 32 character long string so we trim it down to 5 
    $security_code = substr($md5_hash, 15, 5); 

    //Set the session to store the security code
    $_SESSION["security_code"] = $security_code;

    //Set the image width and height 
    $im = imagecreate(80, 25);
    
    //white background and blue text
    $bg = imagecolorallocate($im, 255, 255, 255);
	$textcolor = imagecolorallocate($im, 128, 0, 0);
   
    //Add randomly generated string in white to the image
    ImageString($im, 5, 20, 8, $security_code, $textcolor); 
   
    //Tell the browser what kind of file is come in 
    header("Content-Type: image/jpeg"); 

    //Output the newly created image in jpeg format 
    ImageJpeg($im); 
    
    //Free up resources
    ImageDestroy($im); 
} 
?>
