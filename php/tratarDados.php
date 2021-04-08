<?php
    include 'inicializarBanco.php';

    // Funcao para cadastrar usuario
    function cadastro($conexao){
        // Pega os dados (traduz o dado de data para colocar no banco)
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $data = strtotime($_POST['dataNascimento']);
        $data = date("Y-m-d H:i:s",$data);
        $senha = $_POST['senha'];

        // Insere no banco de dados
        $resultado = mysqli_query($conexao, "INSERT IGNORE INTO t_usuarios (id_user,nome,email,data_nascimento,senha) VALUES (0, '$nome', '$email', '$data', '$senha')");

        // Retorna o sucesso da insercao no banco
        if($resultado){
            echo json_encode("Sucesso!");
        }
    }

    // Funcao para efetuar login
    function login($conexao){
    }

    // Codigo principal
    $tipo = $_POST['tipo'];

    // Checa o tipo de funcao que deve ser chamada
    if($tipo == 'cadastro'){
        cadastro($link);
    }
    if($tipo == 'login'){
        login($link);
    }
    
    // Fecha a conexao com o banco
	mysqli_close($link);

?>
