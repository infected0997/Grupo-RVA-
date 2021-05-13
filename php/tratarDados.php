<?php
    include 'inicializarBanco.php';

    // ~~ FUNCOES ~~ //

    // Funcao para mandar email
    function funcEmail($destino, $titulo, $mensagem){
        //include 'enviarEmail.php';
    }

    // Funcao para autenticar email da conta
    function authConta($conexao){
        // Começa a inicializar a sessão
        session_start();

        // Recebe o token
        $tokenAuth = $_POST['token'];

        // Procura os tokens do banco e os compara com o recebido
        $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_autenticar = '$tokenAuth'");
        if(mysqli_num_rows($resultado) > 0){
            // Se o token for encontrado, o usuario e autenticado no banco
            $coluna = mysqli_fetch_assoc($resultado);
            $idUser = $coluna['id_user'];
            $resultado = mysqli_query($conexao, "UPDATE pessoa SET autenticado = 1 WHERE id_user = '$idUser'");
            $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_autenticar = NULL WHERE id_user = '$idUser'");
            $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE id_user = '$idUser'"); 
        }
        else{
            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Token invalido!';

            echo json_encode($retorno);
            exit;
        }
        // Cria uma sessão com o usuario e salva seu id nela
        $_SESSION['usuario'] = mysqli_fetch_assoc($resultado)['id_user'];
        $_SESSION['id'] = session_id();
        $_SESSION['tempo'] = time();
        $_SESSION['limite'] = 3600;

        $retorno['status'] = 's';
        $retorno['mensagem'] = 'Email autenticado com sucesso!';

        echo json_encode($retorno);
    }

    // Funcao para cadastrar usuario
    function funcCadastro($conexao){
        // Pega os dados (traduz o dado de data para colocar no banco)
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $data = strtotime($_POST['dataNascimento']);
        $data = date("Y-m-d H:i:s",$data);
        $senha = $_POST['senha'];

        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE email = '$email'");

        // Retorna o sucesso da insercao no banco
        if(mysqli_num_rows($resultado) == 0){
            // Insere no banco de dados
            $resultado = mysqli_query($conexao, "INSERT IGNORE INTO pessoa (id_user,nome,email,data_nascimento,senha,autenticado) VALUES (0, '$nome', '$email', '$data', '$senha', 0)");

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
            //funcEmail($email, $title, $msg);

            // Manda a mensagem de sucesso
            $retorno["status"] = "s";
            $retorno["mensagem"] = "Sucesso! Por favor, confirme sua conta pelo seu email.";
            echo json_encode($retorno);
        }
        else{
            // Manda a mensagem de falha
            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Email ja cadastrado!';
            echo json_encode($retorno);
        }
    }

    // Funcao para efetuar login
    function funcLogin($conexao){
        // Começa a inicializar a sessão
        session_start();

        // Pega os dados do login
        $nome = $_POST['nome'];
        $senha = $_POST['senha'];

        // Procura se tem dados correspondentes no banco
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE nome = '$nome' AND senha = '$senha'");
        if(mysqli_num_rows($resultado) == 0){
            // Falha de conta não encontrada
            $retorno["status"] = "n";
            $retorno["mensagem"] = "Usuário e/ou senha inválidos!";
            echo json_encode($retorno);
            exit();
        }
        $row = mysqli_fetch_assoc($resultado);
        if($row['autenticado'] == 0){
            // Falha de usuario não autenticado por email
            $retorno["status"] = "n";
            $retorno["mensagem"] = "Por favor, autentique sua conta pelo seu email!";
            echo json_encode($retorno);
            exit();
        }
        // Cria uma sessão com o usuario e salva seu id nela
        $_SESSION['usuario'] = $row['id_user'];
        $_SESSION['id'] = session_id();
        $_SESSION['tempo'] = time();
        $_SESSION['limite'] = 3600;

        // Retorna o sucesso
        $retorno["status"] = "s";
        $retorno["mensagem"] = "Usuário logado com sucesso!";
        echo json_encode($retorno);
    }

    // Funcao para recuperar conta por email
    function recuperacaoEmail($conexao){
        // Recupera o valor do email e o procura no banco de dados
        $email = $_POST['email'];
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE email = '$email'");
        if(($idUsuario = mysqli_fetch_assoc($resultado)['id_user']) == null){
            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Email Invalido!';
            echo json_encode($retorno);
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
        
        $retorno['status'] = 's';
        $retorno['mensagem'] = 'Email enviado com sucesso!';

        echo json_encode($retorno);
    }

    // Funcao para mudar senha
    function mudarSenha($conexao){
        session_start();
        $token = $_POST['tokenA'];
        $senha = $_POST['novaSenha'];

        $idUsuario = null;

        // Descobre se a recuperacao e feita por token e pega o id por ele, ou por sessao
        if(strlen($token) == 64){
            $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_senha = '$token'");
            $idUsuario = mysqli_fetch_assoc($resultado)['id_user'];
        }
        else{
            $idUsuario = $_SESSION['usuario'];
        }

        // Muda a senha do user
        $resultado = mysqli_query($conexao, "UPDATE pessoa SET senha = '$senha' WHERE id_user = '$idUsuario'");

        // Destroi a sessao
        unset($_SESSION['usuario']);
        unset($_SESSION['id']);
        unset($_SESSION['tempo']);
        unset($_SESSION['limite']);
        session_destroy();

        $retorno['status'] = 's';
        $retorno['mensagem'] = 'Senha trocada com sucesso!';
        echo json_encode($retorno);
    }

    // Funcao para preparar pagina de usuario
    function prepararUser($conexao){
        // Puxa o .php de teste de sessao
        include 'testaSessao.php';
        if($retorno['status'] == 's'){
            $idUser = $_SESSION['usuario'];
            $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE id_user = '$idUser'");
            $row = mysqli_fetch_assoc($resultado);
            $retorno['nome'] = $row['nome'];
        }
        // Retorna o nome de usuario
        echo json_encode($retorno);
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
