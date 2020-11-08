# Desafio desenvolvedor backend

Foi proposto um desafio para classificar os tickes e depois filtrá-los de acordo com os parâmetros informados pelo usuário.

### Resolução: 
- Sobre o Algorítmo:
  - Primeiro foi desenvolvido um algorítimo que classificasse os tickets. 
Foi utilizado o subject para verificar o tipo de prioridade do ticket aberto, mas poderia facilmente utilizar a mensagem ou a mensagem e o subject. 
Como não foi explicado o modo de como fazê-lo, optei pelo subject pois ali continha 
palavras mais fortes para a classificação.
  - Utilizei a função do PHP para verificar a similaridade das palavras. Criei também um array de palavras positivas e 
negativas para fazer a comparação.
  - Salvei a similaridade e a pontuação em um novo arquivo json, bem como a classificação de ALTA ou NORMAL com base nos 70% de similaridade do subject.
  
- Sobre a API
  - Decidi criar uma classe para trabalhar com o novo arquivo json e os parâmetros são passados através de um array().
  - É possível trabalhar com vários parâmetros de envio para que a API possa retornar exatamento o que o usuário deseja:
  
#### PARAMETROS PARA A URL
  - filtro = dataCriacao ou prioridade
  - ordenacao = dataCriacao, dataAtualizacao ou prioridade
  - inicio = data no formato: YYYY-mm-dd
  - termino = data no formato: YYYY-mm-dd
  - tipoPrioridade = alta ou normal (só será utilizada caso o filtro seja por prioridade)
  - paginacao = define o número de resultados por página (por default está marcado com 3 tickets/pagina)
  - pagina = página do resultado que será exibida como resultado
  - impressao = linha (sem quebra de linha) ou cascata (com quebra de linha, mais fácil para a leitura)
   

    
### URL para Testar o desafio
http://desafio.ms7.com.br/ws-api.php

Exemplos de como utilizar os parâmetros acima para manipular o retorno da api:
- http://desafio.ms7.com.br/ws-api.php?pagina=1&paginacao=3&filtro=dataCriacao&impressao=linha
- http://desafio.ms7.com.br/ws-api.php?pagina=1&paginacao=3&filtro=dataCriacao&impressao=cascata
- http://desafio.ms7.com.br/ws-api.php?pagina=1&paginacao=3&filtro=prioridade&ordenacao=dataAtualizacao&impressao=cascata&tipoPrioridade=alta
