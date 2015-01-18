package main

import "github.com/daretta/fkserver"

/*
Create a new MongoDB session, using a database
named "feedbacks". Create a new server using
that session, then begin listening for HTTP requests.
*/
func main() {
	session := fkserver.NewSession("feedback")
	server := fkserver.NewServer(session)
	server.Run()
}
