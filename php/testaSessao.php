<?php
    if(isset($_SESSION['id']) == false){
        $retorno['status'] = 'n';
        $retorno['mensagem'] = 'Não existe sessão';
    }
    else{
        $segundos = time() - $_SESSION['tempo'];

        if($segundos > $_SESSION['sessao']){
            unset($_SESSION['usuario']);
            unset($_SESSION['id']);
            unset($_SESSION['tempo']);
            unset($_SESSION['tempoAuth']);
            unset($_SESSION['sessao']);
            unset($_SESSION['autenticado']);
            session_destroy();

            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Sessão expirada';
        }
        else{
            $segundosAuth = time() - $_SESSION['tempoAuth'];
            $_SESSION['tempo'] = time();
            $retorno['status'] = 's';
            $retorno['mensagem'] = 'Sessão válida!';
            if($segundosAuth > $_SESSION['autenticado']){
                $retorno['auth'] = 'n';
            }
            else{$retorno['auth'] = 's';}
        }
    }
?>