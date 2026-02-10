#!/bin/bash

# Baixar o EDR na pasta /tmp
wget -O /tmp/SentinelAgent_linux_x86_64_v23_3_2_12.deb https://suporte.somaxi.com.br/util/SentinelAgent_linux_x86_64_v23_3_2_12.deb

# Verificar se o download foi bem-sucedido
if [ $? -ne 0 ]; then
  echo "Erro ao baixar o pacote do EDR. Verifique a URL e sua conexão com a internet."
  exit 1
fi

# Comando para instalação
sudo dpkg -i /tmp/SentinelAgent_linux_x86_64_v23_3_2_12.deb

# Verificar se a instalação foi bem-sucedida
if [ $? -ne 0 ]; then
  echo "Erro durante a instalação do pacote. Verifique as permissões ou outros erros relacionados à instalação."
  exit 1
fi

# Substituir <COLOCAR AQUI O TOKEN> com o token real
TOKEN= COLOCAR TOKEN AQUI


# Cadastrar o agente no ambiente do tripulante
sudo sentinelctl management token set $TOKEN

# Verificar se o cadastro do agente foi bem-sucedido
if [ $? -ne 0 ]; then
  echo "Erro ao cadastrar o agente no ambiente do tripulante. Verifique o token e tente novamente."
  exit 1
fi

# Iniciar o monitoramento do agente
sudo sentinelctl control start

# Verificar se o monitoramento foi iniciado com sucesso
if [ $? -ne 0 ]; then
  echo "Erro ao iniciar o monitoramento do agente."
  exit 1
fi

echo "Instalação concluída e agente iniciado com sucesso."

