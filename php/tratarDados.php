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
        $resultado = mysqli_query($conexao, "SELECT * FROM seguranca");
        while($row = mysqli_fetch_assoc($resultado)){
            $tokenBanco = $row["token_autenticar"];
            if($tokenBanco != $tokenAuth){continue;}
            else{
                $idUser = $row["id_user"];
                $resultado = mysqli_query($conexao, "UPDATE pessoa SET autenticado = 1 WHERE id_user = '$idUser'");
                $resultado = mysqli_query($conexao, "UPDATE seguranca SET token_autenticar = 'X' WHERE id_user = '$idUser'");
            }
        }
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
            $idUsuario = ($row = mysqli_fetch_assoc($resultado)["id_user"]);
            $resultado = mysqli_query($conexao, "INSERT IGNORE INTO seguranca (id_user,token_autenticar) VALUES ('$idUsuario', '$token')");

            // Conteudo da mensagem
            $title = "Autenticação de usuario";
            $linque = "http://localhost/index.html#".$token;
            $msg = "<p>Por favor clique no link a seguir para autenticar sua conta em nosso site: ".
                   "<a href=$linque>Link</a></p>";

            //funcEmail($email, $title, $msg);
            ob_end_clean();
            echo json_encode($msg);
        }
        else{
            echo json_encode("Erro na inserção em banco!");
        }
    }

    // Funcao para efetuar login
    function funcLogin($conexao){
    }


    // ~~ CODIGO PRINCIPAL ~~ // 

    // Checa o tipo de funcao que deve ser chamada
    $tipo = $_POST['tipo'];
    if($tipo == 'cadastro'){
        funcCadastro($link);
    }
    if($tipo == 'login'){
        funcLogin($link);
    }
    if($tipo == 'autenticar'){
        authConta($link);
    }
    
    // Fecha a conexao com o banco
	mysqli_close($link);

?>
