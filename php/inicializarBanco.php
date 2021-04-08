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

	// Cria a table t_usuarios
	$resultado = mysqli_query($link, "CREATE TABLE IF NOT EXISTS t_usuarios(
		id_user int(4)  NOT NULL AUTO_INCREMENT ,
		nome varchar(40)  NOT NULL ,
		email varchar(100)  NOT NULL ,
		data_nascimento date  NOT NULL ,
		senha varchar(32)  NOT NULL ,
		PRIMARY KEY(id_user))");

?>
