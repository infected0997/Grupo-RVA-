<?php 

	// Parametros do banco
	$user = 'root';
	$password = 'root';
	$db = '';
	$host = 'localhost';
	$port = 3307;

	// Inicia uma conexao com o banco
	$link = mysqli_init();
	$success = mysqli_real_connect(
   		$link, 
   		$host, 
   		$user, 
   		$password, 
   		$db,
		$port
	);

	// Cria o banco nomeado "banco_RVA" e comeca a usa-lo
	$resultado = mysqli_query($link, "CREATE DATABASE IF NOT EXISTS banco_RVA");
	$resultado = mysqli_query($link, "USE banco_RVA");

	// Cria a table pessoa
	$resultado = mysqli_query($link, "CREATE TABLE IF NOT EXISTS pessoa(
		id_user INT(4)  NOT NULL AUTO_INCREMENT ,
		nome VARCHAR(40)  NOT NULL ,
		email VARCHAR(100)  NOT NULL ,
		data_nascimento date  NOT NULL ,
		senha VARCHAR(32)  NOT NULL ,
		autenticado TINYINT(1) ,
		PRIMARY KEY(id_user))");

	// Cria a table autenticar
	$resultado = mysqli_query($link, "CREATE TABLE IF NOT EXISTS seguranca(
		id_user INT(4)  NOT NULL,
		token_autenticar VARCHAR(128)  NOT NULL ,
		PRIMARY KEY(id_user))");

?>
