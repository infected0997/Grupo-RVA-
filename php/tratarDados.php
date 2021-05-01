<?php
    include 'inicializarBanco.php';

    // ~~ FUNCOES ~~ //

    // Funcao para mandar email
    function funcEmail($destino, $titulo, $mensagem){
        include 'enviarEmail.php';
    }

    // Funcao para autenticar email da conta
    function authConta($conexao){
        // Recebe o token
        $tokenAuth = $_POST['token'];

        // Procura os tokens do banco e os compara com o recebido
        $resultado = mysqli_query($conexao, "SELECT * FROM seguranca");
        while($row = mysqli_fetch_assoc($resultado)){
            $tokenBanco = $row["token_autenticar"];
            if($tokenBanco != $tokenAuth){continue;}

            // Se o token for igual, o usuario e autenticado no banco
            else{
                $idUser = $row["id_user"];
                $resultado = mysqli_query($conexao, "UPDATE pessoa SET autenticado = 1 WHERE id_user = '$idUser'");
                $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_autenticar = NULL WHERE id_user = '$idUser'");
                $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE id_user = '$idUser'");

            }
        }
        // Gera o token de sessao coloca no banco e envia para o usuario
        $tokenSessao = bin2hex(random_bytes(16));
        $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_sessao = '$tokenSessao' WHERE id_user = '$idUser'");
        ob_end_clean();
        echo json_encode($tokenSessao);
    }

    // Funcao para cadastrar usuario
    function funcCadastro($conexao){
        // Pega os dados (traduz o dado de data para colocar no banco)
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $data = strtotime($_POST['dataNascimento']);
        $data = date("Y-m-d H:i:s",$data);
        $senha = $_POST['senha'];

        // Insere no banco de dados
        $resultado = mysqli_query($conexao, "INSERT IGNORE INTO pessoa (id_user,nome,email,data_nascimento,senha,autenticado) VALUES (0, '$nome', '$email', '$data', '$senha', 0)");

        // Retorna o sucesso da insercao no banco
        if($resultado){
            // Cria um token e busca o user_id do usuario para inserir na tabela de autenticacao 
            $token = bin2hex(random_bytes(64));
            $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE nome = '$nome' AND senha = '$senha'");
            $idUsuario = mysqli_fetch_assoc($resultado)["id_user"];
            $resultado = mysqli_query($conexao, "INSERT IGNORE INTO seguranca (id_user,token_autenticar) VALUES ('$idUsuario', '$token')");

            // Conteudo da mensagem
            $title = "Autenticação de usuario";
            $linque = "http://localhost/pages/auth.html#".$token;
            $msg = "<p>Por favor clique no link a seguir para autenticar sua conta em nosso site: ".
                   "<a href=$linque>Link</a></p>";

            // Manda o Email
            funcEmail($email, $title, $msg);

            // Limpa o buffer e manda o sucesso
            ob_end_clean();
            echo json_encode("Sucesso!");
        }
        else{
            echo json_encode("Erro na inserção em banco!");
        }
    }

    // Funcao para efetuar login
    function funcLogin($conexao){
        // Pega os dados do login
        $nome = $_POST['nome'];
        $senha = $_POST['senha'];

        // Procura se tem dados correspondentes no banco
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE nome = '$nome' AND senha = '$senha'");
        if(($idUsuario = mysqli_fetch_assoc($resultado)['id_user']) == null){
            ob_end_clean();
            echo json_encode(false);
            exit();
        }
        // Cria um token de sessao e retorna ele para o usuario
        $tokenSessao = bin2hex(random_bytes(16));
        $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_sessao = '$tokenSessao' WHERE id_user = '$idUsuario'");
        ob_end_clean();
        echo json_encode($tokenSessao);
    }

    // Funcao para recuperar conta por email
    function recuperacaoEmail($conexao){
        // Recupera o valor do email e o procura no banco de dados
        $email = $_POST['email'];
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE email = '$email'");
        if(($idUsuario = mysqli_fetch_assoc($resultado)['id_user']) == null){
            ob_end_clean();
            echo json_encode(false);
            exit();
        }

        // Cria um token para a recuperacao de senha e o coloca no banco
        $tokenSenha = bin2hex(random_bytes(32));
        $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_senha = '$tokenSenha' WHERE id_user = '$idUsuario'");

        // Conteudo da mensagem
        $title = "Mudança de senha";
        $linque = "http://localhost/pages/auth.html#".$tokenSenha;
        $msg = "<p>Por favor clique no link a seguir para mudar sua senha: ".
               "<a href=$linque>Link</a></p>";

        // Manda o Email
        funcEmail($email, $title, $msg);
    }

    // Funcao para mudar senha
    function mudarSenha($conexao){
        $token = $_POST['tokenA'];
        $senha = $_POST['novaSenha'];

        // Descobre qual tipo de token que e e pega o id do usuario
        if(strlen($token) == 32){
            $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_sessao = '$token'");
        }
        else{
            $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_senha = '$token'");
        }
        $idUsuario = mysqli_fetch_assoc($resultado)["id_user"];

        // Muda a senha do user
        $resultado = mysqli_query($conexao, "UPDATE pessoa SET senha = '$senha' WHERE id_user = '$idUsuario'");
        ob_end_clean();
        echo json_encode("");
    }

    // Funcao para preparar pagina de usuario
    function prepararUser($conexao){
        // Pega o token de sessao e procura a conta
        $tokenSessao = $_POST['token'];
        $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_sessao = '$tokenSessao'");
        $idUsuario = mysqli_fetch_assoc($resultado)["id_user"];
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE id_user = '$idUsuario'");

        // Retorna o nome de usuario
        echo json_encode(mysqli_fetch_assoc($resultado)['nome']);
    }

    // ~~ CODIGO PRINCIPAL ~~ // 

    // Checa o tipo de funcao que deve ser chamada
    $tipo = $_POST['tipo'];
    if($tipo == 'cadastro'){
        funcCadastro($link);
    }
    else if($tipo == 'login'){
        funcLogin($link);
    }
    else if($tipo == 'autenticar'){
        authConta($link);
    }
    else if($tipo == 'preparaUser'){
        prepararUser($link);
    }
    else if($tipo == 'recuperarConta'){
        recuperacaoEmail($link);
    }
    else if($tipo == 'mudancaSenha'){
        mudarSenha($link);
    }

    // Fecha a conexao com o banco
	mysqli_close($link);

?>
