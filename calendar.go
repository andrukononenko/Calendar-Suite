// calendar.go - Календарь с событиями на Go (встроенный веб-сервер)
package main

import (
	"embed"
	"encoding/json"
	"fmt"
	"html/template"
	"net/http"
	"os"
	"time"
)

//go:embed calendar.html
var staticFiles embed.FS

type Event struct {
	Time  string `json:"time"`
	Title string `json:"title"`
	Desc  string `json:"desc"`
}

var events map[string][]Event
const dataFile = "events.json"

func loadEvents() {
	file, err := os.ReadFile(dataFile)
	if err != nil {
		events = make(map[string][]Event)
		return
	}
	json.Unmarshal(file, &events)
}

func saveEvents() {
	data, _ := json.MarshalIndent(events, "", "  ")
	os.WriteFile(dataFile, data, 0644)
}

func main() {
	loadEvents()
	http.HandleFunc("/", servePage)
	http.HandleFunc("/api/events", eventsHandler)
	fmt.Println("Сервер запущен на http://localhost:8080")
	http.ListenAndServe(":8080", nil)
}

func servePage(w http.ResponseWriter, r *http.Request) {
	htmlContent, _ := staticFiles.ReadFile("calendar.html")
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Write(htmlContent)
}

func eventsHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	switch r.Method {
	case http.MethodGet:
		json.NewEncoder(w).Encode(events)
	case http.MethodPost:
		var newEvents map[string][]Event
		json.NewDecoder(r.Body).Decode(&newEvents)
		events = newEvents
		saveEvents()
		w.WriteHeader(http.StatusOK)
	default:
		w.WriteHeader(http.StatusMethodNotAllowed)
	}
}
