<?php
    date_default_timezone_set('Etc/UTC');
    require 'PHPMailer/PHPMailerAutoload.php';

    $mail= new PHPMailer;
    $mail->IsSMTP(); 
    $mail->CharSet = 'UTF-8';   
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;     
    $mail->SMTPSecure = 'ssl';  
    $mail->Host = 'smtp.gmail.com'; 
    $mail->Port = 465; 
    $mail->Username = 'rafazeteste@gmail.com'; 
    $mail->Password = 'TeStandoHU1234';
    $mail->SetFrom('rafazeteste@gmail.com', 'RVA');
    $mail->addAddress($destino,'');
    $mail->Subject = $titulo;
    $mail->msgHTML($mensagem);
    
    $mail->send();
?>