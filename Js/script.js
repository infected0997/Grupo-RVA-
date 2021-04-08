$(document).ready(function(){

	funcaoClique();
});

// Armazena funcoes de clique
function funcaoClique(){

	// Funcao de clique de cadastro
	$("#botaoCadastroId").click(function(){

		// Definicao de variaveis auxiliares e limpeza do texto de erro
		var form = ["#usuarioId", "#emailId", "#dataId", "#senhaId", "#confSenhaId"];
		var aux = [];
		var testeCad = false;
		$("#formRespostaId").html("");

		// Obtem os itens do formulario e testa se eles estao vazios para dar erro
		for(cont = 0; cont < form.length; cont++){
			$(form[cont]).removeClass("erro-login");
			aux.push($(form[cont]).val());
			if(aux[cont] == ""){
				testeCad = true;
				$(form[cont]).addClass("erro-login");
				$("#formRespostaId").html("Campos incompletos!");
			}
		}
		if(testeCad){return;}

		// Testa se a senha tem 8 caracteres e letras minusculas e maiuscula
		if(aux[3].length < 8 || aux[3] == aux[3].toLowerCase() || aux[3] == aux[3].toUpperCase()){
			testeCad = true;
			$(form[3]).addClass("erro-login");
			$(form[4]).addClass("erro-login");
			$(form[3]).val("");
			$(form[4]).val("");
			$("#formRespostaId").html("Senha precisa ter no mínimo 8 caracteres, e letras maiúsculas e minúsculas!");
			return;
		}

		// Testa se a senhas sao iguais
		if(aux[3] != aux[4]){
			testeCad = true;
			$(form[4]).addClass("erro-login");
			$(form[4]).val("");
			$("#formRespostaId").html("Confirmação de senha incorreta!");
			return;
		}

		// Manda o formulario para o php de tratamento de dados
		$.ajax({
			type: "POST",
			url: '../php/tratarDados.php',
			data: {
				nome: aux[0],
				email: aux[1],
				dataNascimento: aux[2],
				senha: aux[3]
			},
			success: function(data) {
				$("#formRespostaId").addClass("form-correto");
				$("#formRespostaId").html(data);
			}
		  });
	});
}