$(document).ready(function(){


	// Checa se esta no index para rodar a autenticacao por token
	if(window.location.pathname == "/pages/auth.html"){
		autenticarCadastro();
	}
	prepararPagina();
	funcaoClique();
});

// Funcao para preparar as paginas
function prepararPagina(){
	// Obtem os cookies da pagina e testa se o cookie do site esta la
	var cookies = document.cookie;
	if(cookies.indexOf("RVAtokenSessao=") == -1){return;}

	// Obtem a chave de sessao do usuario pelos cookies
	var chaveSessao = cookies.substring(cookies.indexOf("RVAtokenSessao="), cookies.length);
	chaveSessao = chaveSessao.substring(chaveSessao.indexOf("=")+1, 47);
	console.log(cookies);
	console.log(chaveSessao);
}

// Funcao de autenticar usuario
function autenticarCadastro(){
	// Pega o token da URL
	var tokenAuth = window.location.href.split("#").pop();

	// Manda o formulario para o php de tratamento de dados
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "../php/tratarDados.php",
		data: {
			tipo: 'autenticar',
			token: tokenAuth
		},
		// Imprime mensagem de sucesso ou falha
		success: function(data) {
			console.log(data);
			document.cookie = "RVAtokenSessao="+data+";path = /";
			window.location.href = "http://localhost/index.html";
		}
	});
}

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

		// Transforma a senha em hash
		aux[3] = $.MD5(aux[3]);

		// Manda o formulario para o php de tratamento de dados
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "../php/tratarDados.php",
			data: {
				tipo: 'cadastro',
				nome: aux[0],
				email: aux[1],
				dataNascimento: aux[2],
				senha: aux[3]
			},
			// Imprime mensagem de sucesso ou falha
			success: function(data) {
				$("#formRespostaId").removeClass("form-correto");
				if(data == "Sucesso!"){
					$("#formRespostaId").addClass("form-correto");
					data = data+" Por favor autentique sua conta pelo seu e-mail.";
				}
				$("#formRespostaId").html(data);
			}
		});
	});
}
