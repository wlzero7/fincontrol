<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function enviarCodigo($email, $codigo)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'SEU_EMAIL@gmail.com';
        $mail->Password = 'SUA_SENHA_DE_APP';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(
            'SEU_EMAIL@gmail.com',
            'FinControl'
        );

        $mail->addAddress($email);

        $mail->isHTML(true);

        $mail->Subject = 'Código de verificação';

        $mail->Body = "
            <h2>FinControl</h2>

            <p>Seu código de verificação é:</p>

            <h1>$codigo</h1>

            <p>Não compartilhe este código.</p>
        ";

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;

    }
}