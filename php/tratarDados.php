<?php
    include 'inicializarBanco.php';

    // Pega os dados (traduz o dado de data para colocar no banco)
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $data = strtotime($_POST['dataNascimento']);
    $data = date("Y-m-d H:i:s",$data);
    $senha = $_POST['senha'];

    $resultado = mysqli_query($link, "INSERT IGNORE INTO t_usuarios (id_user,nome,email,data_nascimento,senha) VALUES (0, '$nome', '$email', '$data', '$senha')");

    // Retorna o sucesso da insercao no banco
    echo json_encode("Sucesso!");
    
    // Fecha a conexao com o banco
	mysqli_close($link);
?>