package main

import (
	"encoding/json"
	"log"
	"net/http"
	"reflect"
	"time"

	"github.com/gorilla/context"
	"github.com/julienschmidt/httprouter"
	"github.com/justinas/alice"
	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"
)

// Repo

type Feedback struct {
    Id       bson.ObjectId `json:"id,omitempty" bson:"_id,omitempty"`
    URL     string        `json:"url"`
    Message string        `json:"message"`
}

type FeedbacksCollection struct {
	Data []Feedback `json:"data"`
}

type FeedbackResource struct {
    Data Feedback `json:"data"`
}

type FeedbackRepo struct {
    coll *mgo.Collection
}

func (r *FeedbackRepo) All() (FeedbacksCollection, error) {
    result := FeedbacksCollection{[]Feedback{}}
    err := r.coll.Find(nil).All(&result.Data)
    if err != nil {
        return result, err
    }

    return result, nil
}

func (r *FeedbackRepo) Find(id string) (FeedbackResource, error) {
    result := FeedbackResource{}
    err := r.coll.FindId(bson.ObjectIdHex(id)).One(&result.Data)
    if err != nil {
        return result, err
    }

    return result, nil
}

func (r *FeedbackRepo) Create(feedback *Feedback) error {
    id := bson.NewObjectId()
    _, err := r.coll.UpsertId(id, feedback)
    if err != nil {
        return err
    }

    feedback.Id = id

    return nil
}

func (r *FeedbackRepo) Update(feedback *Feedback) error {
    err := r.coll.UpdateId(feedback.Id, feedback)
    if err != nil {
        return err
    }

    return nil
}

func (r *FeedbackRepo) Delete(id string) error {
    err := r.coll.RemoveId(bson.ObjectIdHex(id))
    if err != nil {
        return err
    }

    return nil
}

// Errors

type Errors struct {
	Errors []*Error `json:"errors"`
}

type Error struct {
	Id     string `json:"id"`
	Status int    `json:"status"`
	Title  string `json:"title"`
	Detail string `json:"detail"`
}

func WriteError(w http.ResponseWriter, err *Error) {
	w.Header().Set("Content-Type", "application/vnd.api+json")
	w.WriteHeader(err.Status)
	json.NewEncoder(w).Encode(Errors{[]*Error{err}})
}

var (
	ErrBadRequest           = &Error{"bad_request", 400, "Bad request", "Request body is not well-formed. It must be JSON."}
	ErrNotAcceptable        = &Error{"not_acceptable", 406, "Not Acceptable", "Accept header must be set to 'application/vnd.api+json'."}
	ErrUnsupportedMediaType = &Error{"unsupported_media_type", 415, "Unsupported Media Type", "Content-Type header must be set to: 'application/vnd.api+json'."}
	ErrInternalServer       = &Error{"internal_server_error", 500, "Internal Server Error", "Something went wrong."}
)

// Middlewares

func recoverHandler(next http.Handler) http.Handler {
	fn := func(w http.ResponseWriter, r *http.Request) {
		defer func() {
			if err := recover(); err != nil {
				log.Printf("panic: %+v", err)
				WriteError(w, ErrInternalServer)
			}
		}()

		next.ServeHTTP(w, r)
	}

	return http.HandlerFunc(fn)
}

func loggingHandler(next http.Handler) http.Handler {
	fn := func(w http.ResponseWriter, r *http.Request) {
		t1 := time.Now()
		next.ServeHTTP(w, r)
		t2 := time.Now()
		log.Printf("[%s] %q %v\n", r.Method, r.URL.String(), t2.Sub(t1))
	}

	return http.HandlerFunc(fn)
}

func acceptHandler(next http.Handler) http.Handler {
	fn := func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("Accept") != "application/vnd.api+json" {
			WriteError(w, ErrNotAcceptable)
			return
		}

		next.ServeHTTP(w, r)
	}

	return http.HandlerFunc(fn)
}

func contentTypeHandler(next http.Handler) http.Handler {
	fn := func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("Content-Type") != "application/vnd.api+json" {
			WriteError(w, ErrUnsupportedMediaType)
			return
		}

		next.ServeHTTP(w, r)
	}

	return http.HandlerFunc(fn)
}

func bodyHandler(v interface{}) func(http.Handler) http.Handler {
	t := reflect.TypeOf(v)

	m := func(next http.Handler) http.Handler {
		fn := func(w http.ResponseWriter, r *http.Request) {
			val := reflect.New(t).Interface()
			err := json.NewDecoder(r.Body).Decode(val)

			if err != nil {
				WriteError(w, ErrBadRequest)
				return
			}

			if next != nil {
				context.Set(r, "body", val)
				next.ServeHTTP(w, r)
			}
		}

		return http.HandlerFunc(fn)
	}

	return m
}

// Main handlers

type appContext struct {
	db *mgo.Database
}

func (c *appContext) feedbacksHandler(w http.ResponseWriter, r *http.Request) {
    repo := FeedbackRepo{c.db.C("feedback")}
    feedbacks, err := repo.All()
    if err != nil {
        panic(err)
    }

    w.Header().Set("Content-Type", "application/vnd.api+json")
    json.NewEncoder(w).Encode(feedbacks)
}

func (c *appContext) feedbackHandler(w http.ResponseWriter, r *http.Request) {
    params := context.Get(r, "params").(httprouter.Params)
    repo := FeedbackRepo{c.db.C("feedback")}
    feedback, err := repo.Find(params.ByName("id"))
    if err != nil {
        panic(err)
    }

    w.Header().Set("Content-Type", "application/vnd.api+json")
    json.NewEncoder(w).Encode(feedback)
}

func (c *appContext) createFeedbackHandler(w http.ResponseWriter, r *http.Request) {
    body := context.Get(r, "body").(*FeedbackResource)
    repo := FeedbackRepo{c.db.C("feedback")}
    err := repo.Create(&body.Data)
    if err != nil {
        panic(err)
    }

    w.Header().Set("Content-Type", "application/vnd.api+json")
    w.WriteHeader(201)
    json.NewEncoder(w).Encode(body)
}

func (c *appContext) updateFeedbackHandler(w http.ResponseWriter, r *http.Request) {
    params := context.Get(r, "params").(httprouter.Params)
    body := context.Get(r, "body").(*FeedbackResource)
    body.Data.Id = bson.ObjectIdHex(params.ByName("id"))
    repo := FeedbackRepo{c.db.C("feedback")}
    err := repo.Update(&body.Data)
    if err != nil {
        panic(err)
    }

    w.WriteHeader(204)
    w.Write([]byte("\n"))
}

func (c *appContext) deleteFeedbackHandler(w http.ResponseWriter, r *http.Request) {
    params := context.Get(r, "params").(httprouter.Params)
    repo := FeedbackRepo{c.db.C("feedback")}
    err := repo.Delete(params.ByName("id"))
    if err != nil {
        panic(err)
    }

    w.WriteHeader(204)
    w.Write([]byte("\n"))
}

// Router

type router struct {
	*httprouter.Router
}

func (r *router) Get(path string, handler http.Handler) {
	r.GET(path, wrapHandler(handler))
}

func (r *router) Post(path string, handler http.Handler) {
	r.POST(path, wrapHandler(handler))
}

func (r *router) Put(path string, handler http.Handler) {
	r.PUT(path, wrapHandler(handler))
}

func (r *router) Delete(path string, handler http.Handler) {
	r.DELETE(path, wrapHandler(handler))
}

func NewRouter() *router {
	return &router{httprouter.New()}
}

func wrapHandler(h http.Handler) httprouter.Handle {
	return func(w http.ResponseWriter, r *http.Request, ps httprouter.Params) {
		context.Set(r, "params", ps)
		h.ServeHTTP(w, r)
	}
}

func main() {
	session, err := mgo.Dial("localhost")
	if err != nil {
		panic(err)
	}
	defer session.Close()
	session.SetMode(mgo.Monotonic, true)

	appC := appContext{session.DB("test")}
	commonHandlers := alice.New(context.ClearHandler, loggingHandler, recoverHandler, acceptHandler)
	router := NewRouter()
    router.Get("/feedbacks/:id", commonHandlers.ThenFunc(appC.feedbackHandler))
    router.Put("/feedbacks/:id", commonHandlers.Append(contentTypeHandler, bodyHandler(FeedbackResource{})).ThenFunc(appC.updateFeedbackHandler))
    router.Delete("/feedbacks/:id", commonHandlers.ThenFunc(appC.deleteFeedbackHandler))
    router.Get("/feedbacks", commonHandlers.ThenFunc(appC.feedbacksHandler))
    router.Post("/feedbacks", commonHandlers.Append(contentTypeHandler, bodyHandler(FeedbackResource{})).ThenFunc(appC.createFeedbackHandler))
	http.ListenAndServe(":8080", router)
}
