Exemplo de requisição post com shell:

```bash
curl --include --header "Authorization: senha-ultra-secreta" \
-X POST -H "Accept: aplication/json" -H "Content-Type: application/json" --data \
'{"hostname": "008.054517","ip": "200.0.1.4","poe_type": true,"model": "hp_comware","qtde_portas": 24,"rack_id": 1,"user_id": 1}' \
http://127.0.0.1:8000/api/equipamentos
```
