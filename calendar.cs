// calendar.cs - Календарь с событиями на C# Windows Forms
using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using Newtonsoft.Json;

namespace CalendarApp
{
    public class Event
    {
        public string Time { get; set; }
        public string Title { get; set; }
        public string Desc { get; set; }
    }

    public class MainForm : Form
    {
        private Label monthLabel;
        private Button prevBtn, nextBtn, todayBtn;
        private TableLayoutPanel calendarPanel;
        private ListBox eventsList;
        private Dictionary<string, List<Event>> events;
        private DateTime currentDate;
        private string selectedDateStr;
        private const string DataFile = "events.json";

        public MainForm()
        {
            Text = "Календарь событий";
            Size = new Size(950, 650);
            LoadEvents();
            currentDate = DateTime.Today;
            InitializeUI();
            DrawCalendar();
        }

        private void LoadEvents()
        {
            if (File.Exists(DataFile))
            {
                string json = File.ReadAllText(DataFile);
                events = JsonConvert.DeserializeObject<Dictionary<string, List<Event>>>(json) ?? new Dictionary<string, List<Event>>();
            }
            else events = new Dictionary<string, List<Event>>();
        }

        private void SaveEvents()
        {
            string json = JsonConvert.SerializeObject(events, Formatting.Indented);
            File.WriteAllText(DataFile, json);
        }

        private void InitializeUI()
        {
            var topPanel = new Panel { Height = 50, Dock = DockStyle.Top };
            monthLabel = new Label { Font = new Font("Arial", 14, FontStyle.Bold), Location = new Point(10, 10), AutoSize = true };
            prevBtn = new Button { Text = "<", Location = new Point(200, 10), Size = new Size(40, 30) };
            nextBtn = new Button { Text = ">", Location = new Point(250, 10), Size = new Size(40, 30) };
            todayBtn = new Button { Text = "Сегодня", Location = new Point(300, 10), Size = new Size(80, 30) };
            topPanel.Controls.AddRange(new Control[] { monthLabel, prevBtn, nextBtn, todayBtn });
            Controls.Add(topPanel);

            calendarPanel = new TableLayoutPanel { Dock = DockStyle.Fill, ColumnCount = 7, RowCount = 7 };
            for (int i = 0; i < 7; i++) calendarPanel.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 14.28f));
            for (int i = 0; i < 7; i++) calendarPanel.RowStyles.Add(new RowStyle(SizeType.Percent, 14.28f));
            // Заголовки дней
            string[] days = { "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс" };
            for (int i = 0; i < 7; i++)
            {
                var lbl = new Label { Text = days[i], TextAlign = ContentAlignment.MiddleCenter, BackColor = Color.LightGray, Dock = DockStyle.Fill };
                calendarPanel.Controls.Add(lbl, i, 0);
            }
            Controls.Add(calendarPanel);

            var rightPanel = new Panel { Width = 250, Dock = DockStyle.Right };
            eventsList = new ListBox { Dock = DockStyle.Fill };
            var addBtn = new Button { Text = "➕ Добавить событие", Dock = DockStyle.Bottom, Height = 40 };
            addBtn.Click += (s, e) => AddEvent();
            rightPanel.Controls.Add(eventsList);
            rightPanel.Controls.Add(addBtn);
            Controls.Add(rightPanel);

            prevBtn.Click += (s, e) => { currentDate = currentDate.AddMonths(-1); DrawCalendar(); };
            nextBtn.Click += (s, e) => { currentDate = currentDate.AddMonths(1); DrawCalendar(); };
            todayBtn.Click += (s, e) => { currentDate = DateTime.Today; DrawCalendar(); };
        }

        private void DrawCalendar()
        {
            monthLabel.Text = currentDate.ToString("MMMM yyyy");
            // Удаляем старые кнопки дней (строки 1-6)
            for (int row = 1; row <= 6; row++)
                for (int col = 0; col < 7; col++)
                {
                    var ctrl = calendarPanel.GetControlFromPosition(col, row);
                    if (ctrl != null) calendarPanel.Controls.Remove(ctrl);
                }
            DateTime firstDay = new DateTime(currentDate.Year, currentDate.Month, 1);
            int startOffset = (int)firstDay.DayOfWeek; // 0=воскресенье -> преобразуем
            startOffset = startOffset == 0 ? 6 : startOffset - 1;
            int daysInMonth = DateTime.DaysInMonth(currentDate.Year, currentDate.Month);
            int day = 1;
            for (int row = 1; row <= 6 && day <= daysInMonth; row++)
            {
                for (int col = 0; col < 7; col++)
                {
                    if (row == 1 && col < startOffset) continue;
                    if (day > daysInMonth) break;
                    DateTime date = new DateTime(currentDate.Year, currentDate.Month, day);
                    string dateStr = date.ToString("yyyy-MM-dd");
                    bool hasEvent = events.ContainsKey(dateStr) && events[dateStr].Count > 0;
                    var btn = new Button
                    {
                        Text = $"{day}\n{(hasEvent ? "📌" : "")}",
                        Dock = DockStyle.Fill,
                        Tag = dateStr
                    };
                    btn.Click += (s, e) => ShowEventsForDate(dateStr);
                    calendarPanel.Controls.Add(btn, col, row);
                    day++;
                }
            }
        }

        private void ShowEventsForDate(string dateStr)
        {
            selectedDateStr = dateStr;
            eventsList.Items.Clear();
            if (events.ContainsKey(dateStr))
            {
                foreach (var ev in events[dateStr])
                    eventsList.Items.Add($"{ev.Time} {ev.Title} - {ev.Desc}");
            }
            else eventsList.Items.Add("Нет событий");
        }

        private void AddEvent()
        {
            if (selectedDateStr == null) { MessageBox.Show("Выберите дату в календаре"); return; }
            var timeBox = new TextBox();
            var titleBox = new TextBox();
            var descBox = new TextBox { Multiline = true, Height = 60 };
            var panel = new TableLayoutPanel { RowCount = 4, ColumnCount = 2, Width = 400 };
            panel.Controls.Add(new Label { Text = "Время:" }, 0, 0);
            panel.Controls.Add(timeBox, 1, 0);
            panel.Controls.Add(new Label { Text = "Название:" }, 0, 1);
            panel.Controls.Add(titleBox, 1, 1);
            panel.Controls.Add(new Label { Text = "Описание:" }, 0, 2);
            panel.Controls.Add(descBox, 1, 2);
            var result = MessageBox.Show(panel, "Добавить событие", MessageBoxButtons.OKCancel);
            if (result == DialogResult.OK && !string.IsNullOrWhiteSpace(titleBox.Text))
            {
                var ev = new Event { Time = timeBox.Text, Title = titleBox.Text, Desc = descBox.Text };
                if (!events.ContainsKey(selectedDateStr)) events[selectedDateStr] = new List<Event>();
                events[selectedDateStr].Add(ev);
                SaveEvents();
                DrawCalendar();
                ShowEventsForDate(selectedDateStr);
            }
        }

        [STAThread]
        static void Main() => Application.Run(new MainForm());
    }
}
