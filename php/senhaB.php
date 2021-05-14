<?php
    function desteganize($file) {
        // Le o arquivo para a memoria
        $img = imagecreatefrompng($file);
      
        // Le as dimensoes da imagem
        $width = imagesx($img);
        $height = imagesy($img);
      
        // Define a mensagem
        $binaryMessage = '';
      
        // Inicializa o buffer de mensagem
        $binaryMessageCharacterParts = [];
      
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
        
                // Extrai a cor
                $rgb = imagecolorat($img, $x, $y);
                $colors = imagecolorsforindex($img, $rgb);
          
                $blue = $colors['blue'];
          
                // Converte azul para binario
                $binaryBlue = decbin($blue);
          
                // Extrai o bit menos significativo para o buffer de mensagem
                $binaryMessageCharacterParts[] = $binaryBlue[strlen($binaryBlue) - 1];
          
                if (count($binaryMessageCharacterParts) == 8) {
                    // Se tiver 8 partes no buffer faz um update na mensagem
                    $binaryCharacter = implode('', $binaryMessageCharacterParts);
                    $binaryMessageCharacterParts = [];
                    if ($binaryCharacter == '00000011') {
                        // Se o caractere de fim de texto for encontrado, para de procurar a mensagem
                        break 2;
                    }
                    else {
                        // Atribui o caractere encontrado na mensagem
                        $binaryMessage .= $binaryCharacter;
                    }
                }
            }
        }
      
        // Converte a mensagem binaria para texto
        $message = '';
        for ($i = 0; $i < strlen($binaryMessage); $i += 8) {
          $character = mb_substr($binaryMessage, $i, 8);
          $message .= chr(bindec($character));
        }
      
        return $message;
    }

    $secretfile = '../img/ciber.png';
    $senhaB = desteganize($secretfile);
    define("SENHAB", $senhaB);
?>