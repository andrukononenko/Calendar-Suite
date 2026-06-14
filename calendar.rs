// calendar.rs - Календарь с событиями на Rust (CLI + JSON)
use serde::{Serialize, Deserialize};
use std::collections::HashMap;
use std::fs;
use std::io::{self, Write};
use chrono::{Local, NaiveDate};

#[derive(Serialize, Deserialize, Clone)]
struct Event {
    time: String,
    title: String,
    desc: String,
}

type EventsMap = HashMap<String, Vec<Event>>;
const DATA_FILE: &str = "events.json";

fn load_events() -> EventsMap {
    if let Ok(data) = fs::read_to_string(DATA_FILE) {
        serde_json::from_str(&data).unwrap_or_else(|_| HashMap::new())
    } else {
        HashMap::new()
    }
}

fn save_events(events: &EventsMap) {
    let data = serde_json::to_string_pretty(events).unwrap();
    fs::write(DATA_FILE, data).unwrap();
}

fn main() {
    let mut events = load_events();
    println!("📅 Календарь с событиями (CLI)");
    loop {
        println!("\n1. Просмотр календаря\n2. Добавить событие\n3. Просмотр событий на дату\n4. Поиск\n5. Выход");
        print!("Выбор: ");
        io::stdout().flush().unwrap();
        let mut choice = String::new();
        io::stdin().read_line(&mut choice).unwrap();
        match choice.trim() {
            "1" => show_calendar(&events),
            "2" => add_event(&mut events),
            "3" => view_events(&events),
            "4" => search_events(&events),
            "5" => break,
            _ => println!("Неверный ввод"),
        }
    }
}

fn show_calendar(events: &EventsMap) {
    let now = Local::now();
    let year = now.year();
    let month = now.month();
    let first_day = NaiveDate::from_ymd_opt(year, month, 1).unwrap();
    let days_in_month = NaiveDate::from_ymd_opt(year, month + 1, 1)
        .unwrap_or_else(|| NaiveDate::from_ymd_opt(year + 1, 1, 1).unwrap())
        .pred_opt()
        .unwrap()
        .day();
    let start_weekday = first_day.weekday().num_days_from_monday(); // 0=пн
    println!("\n{} {}", chrono::format::strftime::strftime("%B", &first_day).unwrap(), year);
    println!("Пн Вт Ср Чт Пт Сб Вс");
    for _ in 0..start_weekday { print!("   "); }
    for day in 1..=days_in_month {
        let date_str = format!("{}-{:02}-{:02}", year, month, day);
        let has_event = if events.contains_key(&date_str) { "📌" } else { " " };
        print!("{:2}{} ", day, has_event);
        if (start_weekday + day) % 7 == 0 { println!(); }
    }
    println!();
}

fn add_event(events: &mut EventsMap) {
    print!("Дата (ГГГГ-ММ-ДД): ");
    io::stdout().flush().unwrap();
    let mut date = String::new();
    io::stdin().read_line(&mut date).unwrap();
    let date = date.trim().to_string();
    print!("Время (ЧЧ:ММ): ");
    let mut time = String::new();
    io::stdin().read_line(&mut time).unwrap();
    print!("Название: ");
    let mut title = String::new();
    io::stdin().read_line(&mut title).unwrap();
    print!("Описание: ");
    let mut desc = String::new();
    io::stdin().read_line(&mut desc).unwrap();
    let event = Event { time: time.trim().to_string(), title: title.trim().to_string(), desc: desc.trim().to_string() };
    events.entry(date).or_insert_with(Vec::new).push(event);
    save_events(events);
    println!("Событие добавлено");
}

fn view_events(events: &EventsMap) {
    print!("Дата (ГГГГ-ММ-ДД): ");
    io::stdout().flush().unwrap();
    let mut date = String::new();
    io::stdin().read_line(&mut date).unwrap();
    let date = date.trim();
    if let Some(ev_list) = events.get(date) {
        for (i, ev) in ev_list.iter().enumerate() {
            println!("{}. {} {} - {}", i+1, ev.time, ev.title, ev.desc);
        }
    } else {
        println!("Нет событий");
    }
}

fn search_events(events: &EventsMap) {
    print!("Ключевое слово: ");
    io::stdout().flush().unwrap();
    let mut keyword = String::new();
    io::stdin().read_line(&mut keyword).unwrap();
    let keyword = keyword.trim().to_lowercase();
    for (date, ev_list) in events {
        for ev in ev_list {
            if ev.title.to_lowercase().contains(&keyword) || ev.desc.to_lowercase().contains(&keyword) {
                println!("{}: {} {}", date, ev.time, ev.title);
            }
        }
    }
}
