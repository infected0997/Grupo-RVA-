<?php
    include 'senhaB.php';
    include 'inicializarBanco.php';

    session_start();

    // ~~ FUNCOES ~~ //

    // Funcao para mandar email
    function funcEmail($destino, $titulo, $mensagem){
        //include 'enviarEmail.php';
    }

    // Funcao para autenticar email da conta
    function authConta($conexao){
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

            echo json_encode(enCripto($retorno));
            exit;
        }
        // Cria uma sessão com o usuario e salva seu id nela
        $_SESSION['usuario'] = mysqli_fetch_assoc($resultado)['id_user'];
        $_SESSION['id'] = session_id();
        $_SESSION['tempo'] = time();
        $_SESSION['tempoAuth'] = time();
        $_SESSION['sessao'] = 3600;
        $_SESSION['autenticado'] = 300;

        $retorno['status'] = 's';
        $retorno['mensagem'] = 'Email autenticado com sucesso!';

        echo json_encode(enCripto($retorno));
    }

    // Funcao para cadastrar usuario
    function funcCadastro($conexao){
        // Pega os dados (traduz o dado de data para colocar no banco)
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $data = strtotime($_POST['dataNascimento']);
        $data = date("Y-m-d H:i:s",$data);
        $senha = $_POST['senha'];

        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE email = '$email' OR nome = '$nome'");

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
            $linque = "http://localhost/Grupo-RVA-/pages/auth.html#".$token;
            $msg = "<p>Por favor clique no link a seguir para autenticar sua conta em nosso site: ".
                   "<a href=$linque>Link</a></p>";

            // Manda o Email
            //funcEmail($email, $title, $msg);

            // Manda a mensagem de sucesso
            $retorno["status"] = "s";
            $retorno["mensagem"] = "Sucesso! Por favor, confirme sua conta pelo seu email.";
            echo json_encode(enCripto($retorno));
        }
        else{
            // Manda a mensagem de falha
            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Email/Usuário ja cadastrado!';
            echo json_encode(enCripto($retorno));
        }
    }

    // Funcao para efetuar login
    function funcLogin($conexao){
        // Pega os dados do login
        $nome = $_POST['nome'];
        $senha = $_POST['senha'];

        // Procura se tem dados correspondentes no banco
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE nome = '$nome' AND senha = '$senha'");
        if(mysqli_num_rows($resultado) == 0){
            // Falha de conta não encontrada
            $retorno["status"] = "n";
            $retorno["mensagem"] = "Usuário e/ou senha inválidos!";
            echo json_encode(enCripto($retorno));
            exit();
        }
        $row = mysqli_fetch_assoc($resultado);
        if($row['autenticado'] == 0){
            // Falha de usuario não autenticado por email
            $retorno["status"] = "n";
            $retorno["mensagem"] = "Por favor, autentique sua conta pelo seu email!";
            echo json_encode(enCripto($retorno));
            exit();
        }
        // Cria uma sessão com o usuario e salva seu id nela
        $_SESSION['usuario'] = $row['id_user'];
        $_SESSION['id'] = session_id();
        $_SESSION['tempo'] = time();
        $_SESSION['tempoAuth'] = time();
        $_SESSION['sessao'] = 3600;
        $_SESSION['autenticado'] = 300;

        // Retorna o sucesso
        $retorno["status"] = "s";
        $retorno["mensagem"] = "Usuário logado com sucesso!";
        echo json_encode(enCripto($retorno));
    }

    // Funcao para recuperar conta por email
    function recuperacaoEmail($conexao){
        // Recupera o valor do email e o procura no banco de dados
        $email = $_POST['email'];
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE email = '$email'");
        if(($idUsuario = mysqli_fetch_assoc($resultado)['id_user']) == null){
            $retorno['status'] = 'n';
            $retorno['mensagem'] = 'Email Invalido!';
            echo json_encode(enCripto($retorno));
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

        echo json_encode(enCripto($retorno));
    }

    // Funcao para mudar senha
    function mudarSenha($conexao){
        $token = $_POST['tokenA'];
        $senha = $_POST['novaSenha'];

        $idUsuario = null;

        // Descobre se a recuperacao e feita por token e pega o id por ele, ou por sessao
        if(strlen($token) == 64){
            $resultado = mysqli_query($conexao, "SELECT * FROM seguranca WHERE token_senha = '$token'");
            $idUsuario = mysqli_fetch_assoc($resultado)['id_user'];
        }
        else{
            // Se 5 minutos de autenticacao passaram, refusa a troca de senha
            if((time() - $_SESSION['tempoAuth']) > $_SESSION['autenticado']){
                $retorno['status'] = 'n';
                $retorno['mensagem'] = 'Usuário precisa se autenticar!';
                echo json_encode(enCripto($retorno));
                exit;
            }
            $idUsuario = $_SESSION['usuario'];
        }

        // Muda a senha do user
        $resultado = mysqli_query($conexao, "UPDATE pessoa SET senha = '$senha' WHERE id_user = '$idUsuario'");

        $retorno['status'] = 's';
        echo json_encode(enCripto($retorno));

        // Destroi a sessao
        unset($_SESSION);
        session_destroy();
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
        if($retorno['status'] == 'e'){
            echo json_encode(enCripto($retorno));
            unset($_SESSION);
            session_destroy();
            exit;
        }
        // Retorna o nome de usuario
        echo json_encode(enCripto($retorno));
    }

    // Funcao descriptografia simetrica
    function desCripto(){
        $dados = $_POST["dados"];

        $mensagem_criptografada = base64_decode($dados);

        // descriptografa a mensagem
        $iv = $_SESSION['vetorInicializacao'];
        $keyEnc = $_SESSION['chaveSecreta'];
        $mensagem_descriptografada = openssl_decrypt($mensagem_criptografada, 'aes-256-cbc', $keyEnc, OPENSSL_ZERO_PADDING, $iv);

        $msgFinal = base64_decode($mensagem_descriptografada);
        
        // Transforma o string da mensagem em um array legivel e joga no $_POST
        $decriptoParametros = explode(",",$msgFinal);
        foreach($decriptoParametros as $parametro){
            $separado = explode(":", str_replace(array("{", "}", '"', "'"), "", $parametro));
            $_POST[$separado[0]] = $separado[1];
        }
    }

    // Funcao criptografia simetrica
    function enCripto($resp){
        // Forma uma frase para criptografar
        $strFinal = '';
        foreach($resp as $num => $resposta){
            $strTemp = $num.':'.$resposta.';';
            $strFinal = $strFinal.$strTemp;
        }

        $iv = $_SESSION['vetorInicializacao'];
        $keyEnc = $_SESSION['chaveSecreta'];

        $respCriptografada = openssl_encrypt($strFinal, 'aes-256-cbc', $keyEnc, 0, $iv);

        return $respCriptografada;
    }

    // Funcao que deleta conta
    function deletaConta($conexao){
        $idUser = $_SESSION['usuario'];
        $resultado = mysqli_query($conexao, "SELECT * FROM pessoa WHERE id_user = '$idUser'");
        $row = mysqli_fetch_assoc($resultado);
        $senhaB = $row['senha'];
        if($senhaB == $_POST['senha']){
            $idUser = $_SESSION['usuario'];
            $resultado = mysqli_query($conexao, "DELETE FROM pessoa WHERE id_user = '$idUser'");
            $resultado = mysqli_query($conexao, "DELETE FROM seguranca WHERE id_user = '$idUser'");
            $retorno['status'] = 's';
            $retorno['mensagem'] = 'Conta deletada!';
            echo json_encode(enCripto($retorno));
            unset($_SESSION);
            session_destroy();
            exit;
        }
        $retorno['status'] = 'n';
        $retorno['mensagem'] = 'Senha incorreta!';
        echo json_encode(enCripto($retorno));
        exit;
    }

    // Funcao mudar chave secreta
    function mudarChaveSec($conexao){
        $ruspBeUni = implode(file("./dataDump.txt"));
        $contador = 0;
        $iVectumSacrosis = $ruspBeUni;
        $divisao = 216.5;
        for($omegaUris = 0; $omegaUris < strlen($iVectumSacrosis); $omegaUris++){
            $iVectumSacrosis[$omegaUris] = chr(ord($iVectumSacrosis[$omegaUris])-1);
        }
        for($contador = 0; $contador < 2; $contador++){
            $valorTemp = '';
            $divisao = intval($divisao*2);
            $cont2 = 534;
            $cont1 = 4;
            for($cont2 = 0; $cont2 < $cont1/2; $cont2++){
                $valorTemp = $valorTemp.substr($iVectumSacrosis, $divisao*($cont2*2)+$divisao, $divisao).substr($iVectumSacrosis, $divisao*($cont2*2), $divisao);
            }
            $iVectumSacrosis = $valorTemp;
        }
        // Puxa os dado criptografado
        $dados = $_POST["criptoChave"];
	    openssl_private_decrypt(base64_decode($dados), $mensagem_descriptografada, $iVectumSacrosis, OPENSSL_ZERO_PADDING);

        // Transforma o string da mensagem em um array legivel e joga no $_POST
        $decriptoParametros = explode(",",$mensagem_descriptografada);
        foreach($decriptoParametros as $parametro){
            $separado = explode(":", str_replace(array("{", "}", '"', "'"), "", $parametro));
            $_POST[$separado[0]] = $separado[1];
        }
	    $_SESSION['chaveSecreta'] = $_POST['chave'];
        $_SESSION['vetorInicializacao'] = $_POST['iv'];
        echo json_encode('s');
        exit;
    }

    // ~~ CODIGO PRINCIPAL ~~ // 

    // Coloca IP na sessao e sai se o usuario tiver um ip diferente da sessao
    if(!isset($_SESSION['ip'])){
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    }
    else if($_SESSION['ip'] != $_SERVER['REMOTE_ADDR']){
        die;
    }

    // Se for nulo, manda a chave publica para o user
    if($_POST == null){
        $chaveB64 = implode(file("../certificado/publica.key"));
        $retorno["chave"] = $chaveB64;
        $retorno["hash"] = md5($chaveB64);
        echo json_encode($retorno);
        exit;
    }
    // Checa se tem a chave secreta
    if(!isset($_SESSION['chaveSecreta'])){
        $ruspBeUni = implode(file("./dataDump.txt"));
        $contador = 0;
        $iVectumSacrosis = $ruspBeUni;
        $divisao = 216.5;
        for($omegaUris = 0; $omegaUris < strlen($iVectumSacrosis); $omegaUris++){
            $iVectumSacrosis[$omegaUris] = chr(ord($iVectumSacrosis[$omegaUris])-1);
        }
        for($contador = 0; $contador < 2; $contador++){
            $valorTemp = '';
            $divisao = intval($divisao*2);
            $cont2 = 534;
            $cont1 = 4;
            for($cont2 = 0; $cont2 < $cont1/2; $cont2++){
                $valorTemp = $valorTemp.substr($iVectumSacrosis, $divisao*($cont2*2)+$divisao, $divisao).substr($iVectumSacrosis, $divisao*($cont2*2), $divisao);
            }
            $iVectumSacrosis = $valorTemp;
        }
        // Puxa os dados criptografados e o hash
        $dados = $_POST["dados"];
        $dadosH = $_POST["hashDados"];
	    openssl_private_decrypt(base64_decode($dados), $mensagem_descriptografada, $iVectumSacrosis, OPENSSL_ZERO_PADDING);

        // Transforma o string da mensagem em um array legivel e joga no $_POST
        $decriptoParametros = explode(",",$mensagem_descriptografada);
        foreach($decriptoParametros as $parametro){
            $separado = explode(":", str_replace(array("{", "}", '"', "'"), "", $parametro));
            $_POST[$separado[0]] = $separado[1];
        }
	    $_SESSION['chaveSecreta'] = $_POST['chave'];
        $_SESSION['vetorInicializacao'] = $_POST['iv'];
        echo json_encode('s');
        exit;
    }
    // Descriptografa a mensagem com a chave secreta
    desCripto();

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
    else if($tipo == 'testando'){
        mudarChaveSec($link);
    }
    else if($tipo == 'deletarConta'){
        deletaConta($link);
    }

    // Fecha a conexao com o banco
	mysqli_close($link);

?>
