package fkserver

import (
	"github.com/go-martini/martini"
	"github.com/martini-contrib/binding"
	"github.com/martini-contrib/render"
	"labix.org/v2/mgo"
)

/*
Wrap the Martini server struct.
*/
type Server *martini.ClassicMartini

/*
Create a new *martini.ClassicMartini server.
We'll use a JSON renderer and our MongoDB
database handler. We define two routes:
"GET /feedbacks" and "POST /feedbacks".
*/
func NewServer(session *DatabaseSession) Server {
	// Create the server and set up middleware.
	m := Server(martini.Classic())
	m.Use(render.Renderer(render.Options{
		IndentJSON: true,
	}))
	m.Use(session.Database())

	// Define the "GET /feedbacks" route.
	m.Get("/feedbacks", func(r render.Render, db *mgo.Database) {
		r.JSON(200, fetchAllFeedback(db))
	})

	// Define the "POST /feedbacks" route.
	m.Post("/feedbacks", binding.Json(Feedback{}),
		func(feedback Feedback,
			r render.Render,
			db *mgo.Database) {

			if feedback.valid() {
				// feedback is valid, insert into database
				err := db.C("feedbacks").Insert(feedback)
				if err == nil {
					// insert successful, 201 Created
					r.JSON(201, feedback)
				} else {
					// insert failed, 400 Bad Request
					r.JSON(400, map[string]string{
						"error": err.Error(),
					})
				}
			} else {
				// feedback is invalid, 400 Bad Request
				r.JSON(400, map[string]string{
					"error": "Not a valid feedback",
				})
			}
		})

	// Return the server. Call Run() on the server to
	// begin listening for HTTP requests.
	return m
}
