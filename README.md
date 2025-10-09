Exemplo de requisição post com shell:

    curl --include --header "Authorization: senha-ultra-secreta" \
    -X POST -H "Content-Type: application/json" --data \
    '{"hostname": "008.054517","ip": "200.0.1.4","poe_type": "no","model": "hp_comware","local": "fcs","position": "RACK-A", "user_id": 2}' \
    http://127.0.0.1:8000/api/equipamentos    
