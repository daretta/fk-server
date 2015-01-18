package fkserver

import "labix.org/v2/mgo"

/*
Each feedback is composed of a first name, last name,
email, age, and short message. When represented in
JSON, ditch TitleCase for snake_case.
*/
type Feedback struct {
	URL       string `json:"url"`
	Email     string `json:"email"`
	Feedback  string `json:"feedback"`
}

/*
I want to make sure all these fields are present. The message
is optional, but if it's present it has to be less than
140 characters--it's a short blurb, not your life story.
*/
func (feedback *Feedback) valid() bool {
	return len(feedback.URL) > 0 &&
		len(feedback.Email) > 0 &&
		len(feedback.Feedback) < 140
}

/*
I'll use this method when displaying all feedback for
"GET /feedbacks". Consult the mgo docs for more info:
http://godoc.org/labix.org/v2/mgo
*/
func fetchAllFeedback(db *mgo.Database) []Feedback {
	feedbacks := []Feedback{}
	err := db.C("feedbacks").Find(nil).All(&feedbacks)
	if err != nil {
		panic(err)
	}

	return feedbacks
}
