# FK-SERVER

run the vargant VM  
```
cd development_enviroment/
vagrant up
```
access to the machine
```
vagrant ssh
```
install the server  
```
go install fk-server
```
run the server
```
fk-server
```
test the service from your PC
```
curl -H 'Accept: application/vnd.api+json' http://192.168.9.9:8080/teas
```
