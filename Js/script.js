var loc = window.location.pathname;
var chavePub = null;
var chaveSec = null;
var iVetor = null;
$(document).ready(function(){
	// Obtem a chave publica do server e coloca em cookie
	chavePub = getCookie("ChaveRVA=");
	if(chavePub == null){
		solicitarChave();
	}
	chavePub = atob(chavePub);

	// Define uma chave privada e vetor de inicializacao e os coloca em cookies
	chaveSec = getCookie("ChaveSec=");
	iVetor = getCookie("iv=");
	if(chaveSec == null){
		defChaveSec();
	} else{
		chaveSec = atob(chaveSec);
		iVetor = atob(iVetor);
	}
	console.log(iVetor);
	// Checa se esta na pagina auth para rodar a autenticacao por token
	if(loc.substring(loc.lastIndexOf('/')+1, loc.lastIndexOf('/')+10) == "auth.html"){	
		autenticarCadastro();
	}
	prepararPagina();
	funcaoClique();
});

// Funcao criptografar simetrica
function enCripto(data){
	// vetor de inicialização
    var iv = CryptoJS.enc.Utf8.parse(iVetor);
    console.log("vator de inicialização: " + iv);

    // converte para JSON e cria um hash
    var valores = JSON.stringify(data);
	var hashData = CryptoJS.MD5(valores).toString();
    // Converte para ut8 e após, para base64
    valores = CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(valores)).toString();
    console.log("valores base64: " + valores);

    // criptografa a mensagem
    // https://cryptojs.gitbook.io/docs/
    var criptografado = CryptoJS.AES.encrypt(valores, chaveSec, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.ZeroPadding
    });
 
    var criptografado_string = criptografado.toString();
    console.log("mensagem criptografada: " + criptografado_string);

    return Array(CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(criptografado_string)).toString(), hashData);
}

// Funcao definir chave secreta
function defChaveSec(){
	// Gera uma chave privada e vetor de inicializacao
	chaveSec = CryptoJS.enc.Hex.stringify(CryptoJS.lib.WordArray.random(32)).toString();
	iVetor = CryptoJS.enc.Hex.stringify(CryptoJS.lib.WordArray.random(16)).toString();
	console.log(chaveSec);
	console.log(iVetor);

	// Cria o pacote com os dados e o hash dele
    var data = {"chave": chaveSec, "iv": iVetor};
    var valores = JSON.stringify(data);
	var dataHash = CryptoJS.MD5(valores).toString();
    console.log("dados: " + valores);

    // cria um objeto da classe JSEncrypt
    var criptografia = new JSEncrypt();
    // adiciona a chave pública ao objeto
    criptografia.setKey(chavePub);

    // Realiza a criptografia
    var mensagem_criptografada = criptografia.encrypt(valores);
    console.log(mensagem_criptografada);

	// Manda os dados e o hash dele
    $.ajax({
        url: "/Grupo-RVA-/php/tratarDados.php",
		async: false, 
        type: 'post', 
        data: {dados: mensagem_criptografada,
			   hashDados: dataHash
			}, 
        dataType: "json",
		success: function(data) {
			secKey = btoa(chaveSec);
			vetorIni = btoa(iVetor);
			document.cookie = "ChaveSec="+secKey+";expires=; path=/";
			document.cookie = "iv="+vetorIni+";expires=; path=/";
		}
    });
}

// Funcao pega cookie
function getCookie(nome){
	// Pega o cookie e o divide em um array
	var cookie = decodeURIComponent(document.cookie);
	var co = cookie.split(';');
	var i;
	for(i = 0; i < co.length; i++) {
		var c = co[i];
		// Testa se o primeiro caractere esta vazio
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		// Testa se o cookie e igual ao que esta procurando, e retorna o valor
		if (c.indexOf(nome) == 0) {
			return c.substring(nome.length, c.length);
		}
	}
	return null;
}

// Funcao para pedir chave publica
function solicitarChave(){
	// Solicita a chave publica do server
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "/Grupo-RVA-/php/tratarDados.php",
		async: false,
		data: {},
		// Caso sucesso volta com a chave e hash
		success: function(data) {
			// compara o hash com o hash da chave
			if(data.hash == CryptoJS.MD5(data.chave).toString()){
				var ch = btoa(data.chave);
				document.cookie = "ChaveRVA="+ch+";expires=; path=/";
				chavePub = ch;
			}
			else{
				solicitarChave();
			}
		}
	});
}

// Funcao para preparar as paginas
function prepararPagina(){
	informacoes = {"tipo":'preparaUser'};
	informacoes = enCripto(informacoes);
	console.log(informacoes[1])
	// Pede informacoes da pagina
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "/Grupo-RVA-/php/tratarDados.php",
		data: {
			dados: informacoes[0],
			hashDados: informacoes[1] 
		},
		// Caso sucesso volta com as informacoes e forma a pagina
		success: function(data) {
			if(data.status == 'n'){
				// Testa se o usuario esta em uma pagina indevida e joga ele para a index
				if(loc.substring(loc.lastIndexOf('/')+1, loc.lastIndexOf('/')+50) == "user.html"){
					window.location.href = "http://localhost/Grupo-RVA-/index.html";
				}
				return;
			}
			// Muda o login para imagem de usuario
			$("#loginUsuario").html("<a href='http://localhost/Grupo-RVA-/pages/user.html' class='nav-link'>Usuario</a>");

			// PREPARA A PAGINA USER.HTML //
			if(window.location.pathname == "/Grupo-RVA-/pages/user.html"){
				$("#nomePessoaId").html(data.nome);
			}
		}
	});
}

// Funcao de autenticar usuario
function autenticarCadastro(){
	// Pega o token da URL
	var tokenAuth = window.location.href.split("#").pop();
	if(tokenAuth.length == 128){
		// Manda o token para autenticar a conta no php
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "../php/tratarDados.php",
			data: {
				tipo: 'autenticar',
				token: tokenAuth
			},
			// Caso sucesso volta a pagina principal
			success: function(data) {
				if(data.status == 's'){
					window.location.href = "http://localhost/Grupo-RVA-/index.html";
				}
			}
		});
	}
	else{
		mudarSenha(tokenAuth);
	}
}

function mudarSenha(token){
	var over =  '<div class="caixa-overlay"><div class="table-overlay">'+
					'<table><tr><td colspan="2"><h4 class="alinha-texto-centro">Insira uma nova senha</h4></td></tr>'+
					'<tr><td colspan="2"><input type="password" id="novaSenhaId" placeholder="Nova senha"></td></tr>'+
					'<tr><td colspan="2"><button id="botaoNovaSenhaId" class="botao-email">Mudar senha</td></tr>'+
					'<tr><td id="formRespostaId" class="form-erro"></td></tr>'+
			  		'</table></div></div>';
		
		// Ativa o overlay
		$("#dOverlay").html(over);
		$("#dOverlay").show();

		// Manda a nova senha para o php
		$("#botaoNovaSenhaId").click(function(){
			var senha = $("#novaSenhaId").val();

			// Testa se a senha tem 8 caracteres e letras minusculas e maiuscula
			if(senha.length < 12 || senha == senha.toLowerCase() || senha == senha.toUpperCase()){
				return;
			}

			// Transforma a senha em hash
			senha = $.MD5(senha);

			// Manda o formulario para o php buscar os dados no banco
			$.ajax({
				type: "POST",
				dataType: "json",
				url: "../php/tratarDados.php",
				data: {
					tipo: 'mudancaSenha',
					novaSenha: senha,
					tokenA: token
				},
				// Se a mudança der certo, volta para o index
				success: function(data) {
					if(data.status = 's'){
						$("#dOverlay").hide();
						$("#dOverlay").html();
						window.location.href = "http://localhost/Grupo-RVA-/index.html";
					}
				}
			});
		});
}

// Armazena funcoes de clique
function funcaoClique(){
	// Funcao clique para formatar a pagina para cadastro
	$("#formularioCadastroId").click(function(){
		var form =  '<tr><td><h1>Cadastro</h1></td></tr>'+
					'<tr><td><input type="text" id="usuarioId" placeholder="Nome de usuario"></td></tr>'+
	      			'<tr><td><input type="email" id="emailId" placeholder="E-mail"></td></tr>'+
					'<tr><td><input type="date" id="dataId" placeholder="DD/MM/AAAA"></td></tr>'+
					'<tr><td><input type="password" id="senhaId" placeholder="Senha"></td></tr>'+
					'<tr><td><input type="password" id="confSenhaId" placeholder="Confirmar senha"></td></tr>'+
					'<tr><td><span>Ja tem conta?<a id="formularioLoginId" class="esquecer-senha mover-texto">Logue aqui!</a></span></td></tr>'+
					'<tr><td colspan="2"><button id="botaoCadastroId" class="botao-login">Cadastrar</td></tr>'+
					'<tr><td id="formRespostaId" class="form-erro"></td></tr>';
		
		$("#cadastroLoginId").html(form);
		funcaoClique();
	});

	// Funcao clique para formatar a pagina para login
	$("#formularioLoginId").click(function(){
		document.location.reload(true);
	});

	// Funcao clique esqueceu a senha
	$("#esqueciSenhaId").click(function(){
		var over =  '<div class="caixa-overlay"><div class="table-overlay">'+
					'<table><tr><td colspan="2"><h4 class="alinha-texto-centro">Insira seu email abaixo</h4></td></tr>'+
					'<tr><td colspan="2"><input type="email" id="emailRecuperarId" placeholder="Endereco de email da conta"></td></tr>'+
					'<tr><td colspan="2"><button id="botaoRecuperarId" class="botao-email">Recuperar Senha</td></tr>'+
					'<tr><td id="formRecEmailId" class="form-erro"></td></tr>'+
			  		'</table></div></div>';
		
		// Ativa o overlay
		$("#dOverlay").html(over);
		$("#dOverlay").show();

		// Manda o nome de email para o php
		$("#botaoRecuperarId").click(function(){
			var nomeEmail = $("#emailRecuperarId").val();

			// Manda o formulario para o php buscar os dados no banco
			$.ajax({
				type: "POST",
				dataType: "json",
				url: "../php/tratarDados.php",
				data: {
					tipo: 'recuperarConta',
					email: nomeEmail
				},
				// Esconde o overlay
				success: function(data) {
					$("#formRespostaId").removeClass("form-correto");
					if(data.status == "s"){
						$("#formRespostaId").addClass("form-correto");
					}
					$("#dOverlay").hide();
					$("#dOverlay").html();
					$("#formRespostaId").html(data.mensagem);
				}
			});
		});
	});

	// Funcao de clique chamar alteracao de senha
	$("#alterarSenhaId").click(function(){
		mudarSenha(null);
	});

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
		if(aux[3].length < 12 || aux[3] == aux[3].toLowerCase() || aux[3] == aux[3].toUpperCase()){
			testeCad = true;
		}
		var testeSimbolo = "!@#$%^&*";
		var simboloTodos = true;
		for(cont = 0; cont < testeSimbolo.length; cont++){
			if(aux[3].indexOf(testeSimbolo.charAt(cont)) != -1){
				simboloTodos = false;
				break;
			}
		}
		if(testeCad || simboloTodos){
			$(form[3]).addClass("erro-login");
			$(form[4]).addClass("erro-login");
			$(form[3]).val("");
			$(form[4]).val("");
			$("#formRespostaId").html("Senha precisa ter no mínimo 12 caracteres, letras maiúsculas e minúsculas, e 1 simbolo!");
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
				if(data.status == "s"){
					$("#formRespostaId").addClass("form-correto");
				}
				$("#formRespostaId").html(data.mensagem);
			}
		});
	});


	// Funcao de clique de login
	$("#botaoLoginId").click(function(){

		// Definicao de variaveis auxiliares e limpeza do texto de erro
		var form = ["#usuarioId", "#senhaId"];
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
				$("#formRespostaId").removeClass("form-correto");
				$("#formRespostaId").html("Campos incompletos!");
			}
		}
		if(testeCad){return;}

		// Transforma a senha em hash
		aux[1] = $.MD5(aux[1]);

		// Manda o formulario para o php buscar os dados no banco
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "../php/tratarDados.php",
			data: {
				tipo: 'login',
				nome: aux[0],
				senha: aux[1]
			},
			// Imprime mensagem de sucesso ou falha
			success: function(data) {
				$("#formRespostaId").removeClass("form-correto");
				if(data.status == "s"){
					$("#formRespostaId").addClass("form-correto");
					window.location.href = "http://localhost/Grupo-RVA-/index.html";
				}
				$("#formRespostaId").html(data.mensagem);
			}
		});
	});
}
