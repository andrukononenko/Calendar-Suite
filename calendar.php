<?php
// calendar.php - Календарь с событиями на PHP (сессия/файл)
session_start();
$dataFile = 'events.json';
if (!file_exists($dataFile)) file_put_contents($dataFile, '{}');
$events = json_decode(file_get_contents($dataFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action === 'save') {
        $newEvents = json_decode($_POST['events'], true);
        file_put_contents($dataFile, json_encode($newEvents, JSON_PRETTY_PRINT));
        exit('ok');
    }
    if ($action === 'add') {
        $date = $_POST['date'];
        $time = $_POST['time'];
        $title = $_POST['title'];
        $desc = $_POST['desc'];
        if (!isset($events[$date])) $events[$date] = [];
        $events[$date][] = ['time'=>$time, 'title'=>$title, 'desc'=>$desc];
        file_put_contents($dataFile, json_encode($events, JSON_PRETTY_PRINT));
        exit('ok');
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP Календарь событий</title>
    <style>
        * { box-sizing: border-box; font-family: system-ui; }
        body { background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .nav button { background: #3498db; border: none; color: white; padding: 6px 12px; border-radius: 20px; cursor: pointer; }
        .calendar { padding: 20px; }
        .weekdays, .days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .day { background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 10px; text-align: center; cursor: pointer; min-height: 80px; }
        .day.today { background: #fff3cd; }
        .event-indicator { font-size: 20px; }
        .events-panel { background: #f8f9fa; padding: 20px; border-left: 1px solid #dee2e6; width: 300px; }
        .flex { display: flex; }
        @media (max-width: 768px) { .flex { flex-direction: column; } .events-panel { width: auto; } }
        button { background: #28a745; border: none; color: white; padding: 8px 16px; border-radius: 30px; cursor: pointer; margin-top: 10px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 20px; width: 350px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="nav">
            <button id="prevMonth">◀</button>
            <span id="monthYear" style="font-weight:bold; margin:0 15px;"></span>
            <button id="nextMonth">▶</button>
            <button id="todayBtn">Сегодня</button>
        </div>
        <div><input type="text" id="searchInput" placeholder="Поиск событий"> <button id="searchBtn">🔍</button></div>
    </div>
    <div class="flex">
        <div class="calendar">
            <div class="weekdays" id="weekdays"></div>
            <div class="days" id="daysGrid"></div>
        </div>
        <div class="events-panel">
            <h3>События на <span id="selectedDate"></span></h3>
            <div id="eventsList"></div>
            <button id="addEventBtn">➕ Добавить</button>
        </div>
    </div>
</div>
<div id="eventModal" class="modal">
    <div class="modal-content">
        <h3>Событие</h3>
        <input type="text" id="eventTime" placeholder="Время ЧЧ:ММ"><br>
        <input type="text" id="eventTitle" placeholder="Название *"><br>
        <textarea id="eventDesc" rows="3" placeholder="Описание"></textarea><br>
        <button id="saveModalBtn">Сохранить</button>
        <button id="cancelModalBtn">Отмена</button>
    </div>
</div>
<script>
    let currentDate = new Date();
    let events = <?php echo json_encode($events); ?>;
    let selectedDateStr = null;

    const monthNames = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    const weekdays = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];

    function saveToServer() {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'action=save&events=' + encodeURIComponent(JSON.stringify(events))
        });
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        let startOffset = firstDay.getDay(); // 0 вс
        startOffset = startOffset === 0 ? 6 : startOffset - 1;
        const daysInMonth = new Date(year, month+1, 0).getDate();
        const today = new Date();
        document.getElementById('monthYear').innerText = `${monthNames[month]} ${year}`;
        const daysGrid = document.getElementById('daysGrid');
        daysGrid.innerHTML = '';
        for (let i = 0; i < 42; i++) {
            const dayNum = i - startOffset + 1;
            if (dayNum > 0 && dayNum <= daysInMonth) {
                const dateObj = new Date(year, month, dayNum);
                const dateStr = dateObj.toISOString().split('T')[0];
                const isToday = dateObj.toDateString() === today.toDateString();
                const hasEvents = events[dateStr] && events[dateStr].length > 0;
                const div = document.createElement('div');
                div.className = `day ${isToday ? 'today' : ''}`;
                div.innerHTML = `<div>${dayNum}</div>${hasEvents ? '<div class="event-indicator">📌</div>' : ''}`;
                div.onclick = () => selectDate(dateStr);
                daysGrid.appendChild(div);
            } else {
                const empty = document.createElement('div');
                empty.className = 'day';
                empty.style.background = '#f8f9fa';
                daysGrid.appendChild(empty);
            }
        }
        const weekdaysDiv = document.getElementById('weekdays');
        weekdaysDiv.innerHTML = weekdays.map(d => `<div style="font-weight:bold;">${d}</div>`).join('');
    }

    function selectDate(dateStr) {
        selectedDateStr = dateStr;
        document.getElementById('selectedDate').innerText = dateStr;
        const container = document.getElementById('eventsList');
        container.innerHTML = '';
        if (events[dateStr]) {
            events[dateStr].forEach((ev, idx) => {
                const div = document.createElement('div');
                div.className = 'event-item';
                div.innerHTML = `<strong>${ev.time}</strong> ${ev.title}<br><small>${ev.desc}</small>
                                 <button onclick="editEvent('${dateStr}', ${idx})">✏️</button>
                                 <button onclick="deleteEvent('${dateStr}', ${idx})">🗑</button>`;
                container.appendChild(div);
            });
        } else container.innerHTML = '<p>Нет событий</p>';
    }

    function addEvent() {
        if (!selectedDateStr) { alert('Сначала выберите дату'); return; }
        document.getElementById('eventTime').value = '';
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventDesc').value = '';
        document.getElementById('eventModal').style.display = 'flex';
        window.currentEdit = null;
    }

    function editEvent(dateStr, idx) {
        const ev = events[dateStr][idx];
        document.getElementById('eventTime').value = ev.time;
        document.getElementById('eventTitle').value = ev.title;
        document.getElementById('eventDesc').value = ev.desc;
        document.getElementById('eventModal').style.display = 'flex';
        window.currentEdit = {date: dateStr, idx: idx};
    }

    function deleteEvent(dateStr, idx) {
        if (confirm('Удалить событие?')) {
            events[dateStr].splice(idx,1);
            if (events[dateStr].length === 0) delete events[dateStr];
            saveToServer();
            selectDate(selectedDateStr);
            renderCalendar();
        }
    }

    document.getElementById('saveModalBtn').onclick = () => {
        const time = document.getElementById('eventTime').value;
        const title = document.getElementById('eventTitle').value.trim();
        if (!title) { alert('Название обязательно'); return; }
        const desc = document.getElementById('eventDesc').value;
        if (window.currentEdit) {
            events[window.currentEdit.date][window.currentEdit.idx] = {time, title, desc};
        } else {
            if (!events[selectedDateStr]) events[selectedDateStr] = [];
            events[selectedDateStr].push({time, title, desc});
        }
        saveToServer();
        document.getElementById('eventModal').style.display = 'none';
        selectDate(selectedDateStr);
        renderCalendar();
    };
    document.getElementById('cancelModalBtn').onclick = () => document.getElementById('eventModal').style.display = 'none';
    document.getElementById('prevMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); };
    document.getElementById('nextMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); };
    document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); renderCalendar(); };
    document.getElementById('addEventBtn').onclick = addEvent;
    document.getElementById('searchBtn').onclick = () => {
        const kw = document.getElementById('searchInput').value.toLowerCase();
        if (!kw) return;
        let res = [];
        for (let d in events) {
            events[d].forEach(ev => {
                if (ev.title.toLowerCase().includes(kw) || ev.desc.toLowerCase().includes(kw))
                    res.push(`${d}: ${ev.time} ${ev.title}`);
            });
        }
        alert(res.join('\n') || 'Ничего не найдено');
    };
    renderCalendar();
    // выбрать сегодняшнюю дату по умолчанию
    const todayStr = new Date().toISOString().split('T')[0];
    selectDate(todayStr);
</script>
</body>
</html>
