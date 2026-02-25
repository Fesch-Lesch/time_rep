import os
import time
import subprocess
import pandas as pd
import matplotlib.pyplot as plt
from datetime import datetime
from fpdf import FPDF

# --- КОНФИГУРАЦИЯ ---
TARGET_HOST = "http://localhost:3000"
USERS_COUNT = 50       # Количество одновременных пользователей
SPAWN_RATE = 5         # Сколько пользователей добавляется в секунду
RUN_TIME = "20s"       # Время теста (например, 30 секунд)
FONT_PATH = "times.ttf"
CSV_PREFIX = "load_test_data"

# Очистка старых данных
if os.path.exists(f"{CSV_PREFIX}_stats.csv"):
    os.remove(f"{CSV_PREFIX}_stats.csv")

print(f"🚀 Запуск нагрузочного теста: {USERS_COUNT} юзеров на {RUN_TIME}...")

# 1. Запуск Locust в режиме без интерфейса (headless)
cmd = [
    "locust",
    "-f", "locustfile.py",
    "--headless",
    "-u", str(USERS_COUNT),
    "-r", str(SPAWN_RATE),
    "--run-time", RUN_TIME,
    "--host", TARGET_HOST,
    "--csv", CSV_PREFIX
]

subprocess.run(cmd)

print("📊 Обработка данных и рисование графиков...")

# 2. Чтение данных
try:
    df = pd.read_csv(f"{CSV_PREFIX}_stats.csv")
except FileNotFoundError:
    print("❌ Ошибка: Файл данных не создан. Возможно, Locust не запустился.")
    exit()

# 3. Рисование графика (Response Time)
plt.figure(figsize=(10, 6))
# Убираем строки 'Aggregated' для графика
df_clean = df[df['Name'] != 'Aggregated']
names = df_clean['Name']
times = df_clean['Average Response Time']

plt.barh(names, times, color='skyblue')
plt.xlabel('Среднее время ответа (мс)')
plt.title('Производительность страниц')
plt.grid(axis='x', linestyle='--', alpha=0.7)
graph_filename = "load_graph.png"
plt.savefig(graph_filename, bbox_inches='tight')
plt.close()

# 4. Генерация PDF
print("📄 Создание PDF отчета...")

class PDF(FPDF):
    def header(self):
        self.add_font('TimesRus', '', FONT_PATH, uni=True)
        self.set_font('TimesRus', '', 16)
        self.cell(0, 10, 'Отчет о нагрузочном тестировании DnD', 0, 1, 'C')
        self.ln(5)

    def footer(self):
        pass

pdf = PDF()
pdf.set_auto_page_break(auto=True, margin=15)
pdf.add_font('TimesRus', '', FONT_PATH, uni=True)

pdf.add_page()

# Параметры теста
pdf.set_font("TimesRus", "", 12)
pdf.set_text_color(0, 0, 0)

pdf.cell(0, 8, f"Дата проверки: {datetime.now().strftime('%d.%m.%Y %H:%M')}", ln=1)
pdf.cell(0, 8, f"Целевой хост: {TARGET_HOST}", ln=1)
pdf.cell(0, 8, f"Пользователей: {USERS_COUNT} (Прирост: {SPAWN_RATE}/сек)", ln=1)
pdf.cell(0, 8, f"Длительность: {RUN_TIME}", ln=1)
pdf.ln(5)

# Таблица результатов
pdf.set_font("TimesRus", "", 12)
pdf.set_fill_color(240, 240, 240)

# Заголовки таблицы
headers = ["Запрос (Страница)", "Запросов", "Ср. время (мс)", "Ошибки"]
col_widths = [80, 30, 40, 30]

for i, h in enumerate(headers):
    pdf.cell(col_widths[i], 10, h, 1, 0, 'C', True)
pdf.ln()

# Данные таблицы
for index, row in df.iterrows():
    if row['Name'] == 'Aggregated': continue # Пропускаем строку итогов пока
    
    # Раскраска строк
    pdf.set_fill_color(255, 255, 255)
    
    # Если время ответа > 500мс, подсвечиваем красным (медленно)
    if row['Average Response Time'] > 500:
        pdf.set_text_color(200, 0, 0)
    else:
        pdf.set_text_color(0, 0, 0)

    pdf.cell(col_widths[0], 8, str(row['Name']), 1)
    pdf.cell(col_widths[1], 8, str(row['Request Count']), 1, 0, 'C')
    pdf.cell(col_widths[2], 8, f"{row['Average Response Time']:.1f}", 1, 0, 'C')
    
    # Если есть ошибки - красный текст
    if row['Failure Count'] > 0:
        pdf.set_text_color(255, 0, 0)
    else:
        pdf.set_text_color(0, 150, 0)
    pdf.cell(col_widths[3], 8, str(row['Failure Count']), 1, 0, 'C')
    
    pdf.set_text_color(0, 0, 0)
    pdf.ln()

pdf.ln(10)

# Вставка графика
pdf.set_font("TimesRus", "", 14)
pdf.cell(0, 10, "График производительности:", ln=1)
if os.path.exists(graph_filename):
    pdf.image(graph_filename, x=15, w=180)

# Сохранение
now = datetime.now()
filename = f"Нагрузочный_тест_{now.strftime('%d.%m.%Y_%H-%M')}.pdf"
pdf.output(filename)

# Уборка мусора
for f in [graph_filename, f"{CSV_PREFIX}_stats.csv", f"{CSV_PREFIX}_stats_history.csv", f"{CSV_PREFIX}_failures.csv", f"{CSV_PREFIX}_exceptions.csv"]:
    if os.path.exists(f):
        os.remove(f)

print(f"✅ Готово! Отчет сохранен как: {filename}") 
