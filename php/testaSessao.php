<?php
    session_start();
    if(isset($_SESSION['id']) == false){
        $retorno['status'] = 'n';
        $retorno['mensagem'] = 'Não existe sessão';
    }
    else{
        $segundos = time() - $_SESSION['tempo'];

        if($segundos > $_SESSION['limite']){
            unset($_SESSION['usuario']);
            unset($_SESSION['id']);
            unset($_SESSION['tempo']);
            unset($_SESSION['limite']);
            session_destroy();

            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Sessão expirada';
        }
        else{
            $_SESSION['tempo'] = time();
            $retorno['status'] = 's';
            $retorno['mensagem'] = 'Sessão válida!';
        }
    }
?>