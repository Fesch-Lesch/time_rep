import os
import glob
import subprocess
import pandas as pd
import matplotlib.pyplot as plt
from datetime import datetime
from config import Config
from base_pdf import BasePDF

class LoadTester:
    def __init__(self, locustfile=None, reports_dir=None, host=None, users=None, spawn_rate=None, run_time=None):
        self.config = Config()
        self.locustfile = locustfile or self.config.LOAD_TEST_LOCUSTFILE
        self.reports_dir = reports_dir or self.config.REPORTS_DIR
        self.host = host or self.config.LOAD_TEST_HOST
        self.users = users or self.config.LOAD_TEST_USERS
        self.spawn_rate = spawn_rate or self.config.LOAD_TEST_SPAWN_RATE
        self.run_time = run_time or self.config.LOAD_TEST_RUN_TIME
        self.csv_prefix = self.config.LOAD_TEST_CSV_PREFIX
        self.graph_file = "load_graph.png"
        
    def check_locustfile(self):
        if not os.path.exists(self.locustfile):
            print(f"❌ Файл сценария '{self.locustfile}' не найден. Создаю новый...")
            self._create_default_locustfile()
        return True
    
    def _create_default_locustfile(self):
        content = '''from locust import HttpUser, task, between

class DnDUser(HttpUser):
    wait_time = between(1, 3)
    
    def on_start(self):
        self.client.post("/dnd-site/login.php", {
            "username": "admin",
            "password": "password"
        })
    
    @task(3)
    def view_dashboard(self):
        self.client.get("/dnd-site/dashboard.php")
    
    @task(1)
    def view_bestiary(self):
        self.client.get("/dnd-site/public/bestiary-view.php")
    
    @task(1)
    def view_students(self):
        self.client.get("/dnd-site/public/student-rating.php")
'''
        with open(self.locustfile, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"✅ Файл сценария создан: {self.locustfile}")
    
    def run_test(self):
        print("\n" + "="*60)
        print("🚀 ЗАПУСК НАГРУЗОЧНОГО ТЕСТИРОВАНИЯ")
        print("="*60)
        
        for f in glob.glob(f"{self.csv_prefix}*"):
            try: os.remove(f)
            except: pass
        
        print(f"📊 Параметры:")
        print(f"   • Хост: {self.host}")
        print(f"   • Пользователей: {self.users}")
        print(f"   • Прирост: {self.spawn_rate}/сек")
        print(f"   • Длительность: {self.run_time}")
        print(f"   • Файл сценария: {self.locustfile}")
        
        cmd = [
            "locust", "-f", self.locustfile, "--headless",
            "-u", str(self.users),
            "-r", str(self.spawn_rate),
            "--run-time", self.run_time,
            "--host", self.host,
            "--csv", self.csv_prefix
        ]
        
        try:
            result = subprocess.run(cmd, capture_output=True, text=True)
            if result.returncode != 0:
                print(f"❌ Ошибка выполнения: {result.stderr}")
                return False
        except FileNotFoundError:
            print("❌ Locust не установлен. Установите: pip install locust")
            return False
        
        print("✅ Тест завершён")
        return True
    
    def process_results(self):
        stats_file = f"{self.csv_prefix}_stats.csv"
        if not os.path.exists(stats_file):
            print("❌ Файл с результатами не найден")
            return None
        try:
            df = pd.read_csv(stats_file)
            print(f"📊 Обработано данных: {len(df)} записей")
            return df
        except Exception as e:
            print(f"❌ Ошибка чтения данных: {e}")
            return None
    
    def create_graph(self, df):
        try:
            plt.figure(figsize=(12, 6))
            df_clean = df[df['Name'] != 'Aggregated']
            if len(df_clean) == 0:
                print("⚠️ Нет данных для построения графика")
                return False
            
            names = [n.split('/')[-1] for n in df_clean['Name']]
            times = df_clean['Average Response Time']
            colors = ['#ff6b6b' if t > 500 else '#4ecdc4' for t in times]
            bars = plt.barh(names, times, color=colors)
            
            for bar, val in zip(bars, times):
                plt.text(val + 5, bar.get_y() + bar.get_height()/2, f'{val:.1f} мс', va='center')
            
            plt.xlabel('Среднее время ответа (мс)')
            plt.title('Производительность страниц')
            plt.grid(axis='x', linestyle='--', alpha=0.7)
            plt.axvline(x=500, color='red', linestyle='--', alpha=0.5, label='Порог 500 мс')
            plt.legend()
            plt.tight_layout()
            plt.savefig(self.graph_file, dpi=150, bbox_inches='tight')
            plt.close()
            print(f"✅ График сохранён: {self.graph_file}")
            return True
        except Exception as e:
            print(f"❌ Ошибка создания графика: {e}")
            return False
    
    def generate_report(self, df):
        print("\n📄 Создание PDF отчёта...")
        os.makedirs(self.reports_dir, exist_ok=True)
        
        pdf = BasePDF(self.config.FONT_PATH)
        pdf.add_page()
        pdf.add_custom_font()
        
        pdf.set_font('TimesRus', '', 16)
        pdf.cell(0, 10, 'Отчёт о нагрузочном тестировании DnD', 0, 1, 'C')
        pdf.ln(5)
        
        pdf.set_font('TimesRus', '', 12)
        pdf.cell(0, 8, f"Дата: {datetime.now().strftime('%d.%m.%Y %H:%M')}", ln=1)
        pdf.cell(0, 8, f"Хост: {self.host}", ln=1)
        pdf.cell(0, 8, f"Пользователей: {self.users} (прирост {self.spawn_rate}/сек)", ln=1)
        pdf.cell(0, 8, f"Длительность: {self.run_time}", ln=1)
        pdf.cell(0, 8, f"Файл сценария: {self.locustfile}", ln=1)
        pdf.ln(10)
        
        pdf.set_font('TimesRus', '', 11)
        pdf.set_fill_color(240,240,240)
        headers = ["Страница", "Запросов", "Ср. время (мс)", "Ошибки", "Медиана", "95%"]
        widths = [70, 25, 30, 25, 25, 25]
        for i, h in enumerate(headers):
            pdf.cell(widths[i], 10, h, 1, 0, 'C', True)
        pdf.ln()
        
        df_display = df[df['Name'] != 'Aggregated']
        for _, row in df_display.iterrows():
            color = (200,0,0) if row['Average Response Time'] > 500 else (0,0,0)
            pdf.set_text_color(*color)
            name = row['Name'].split('/')[-1]
            pdf.cell(widths[0], 8, name, 1)
            pdf.cell(widths[1], 8, str(int(row['Request Count'])), 1, 0, 'C')
            pdf.cell(widths[2], 8, f"{row['Average Response Time']:.1f}", 1, 0, 'C')
            
            err_color = (255,0,0) if row['Failure Count'] > 0 else (0,150,0)
            pdf.set_text_color(*err_color)
            pdf.cell(widths[3], 8, str(int(row['Failure Count'])), 1, 0, 'C')
            
            pdf.set_text_color(0,0,0)
            pdf.cell(widths[4], 8, f"{row['Median Response Time']:.1f}", 1, 0, 'C')
            pdf.cell(widths[5], 8, f"{row['95%']:.1f}", 1, 0, 'C')
            pdf.ln()
        
        pdf.ln(10)
        if os.path.exists(self.graph_file):
            pdf.set_font('TimesRus', '', 14)
            pdf.cell(0, 10, "График производительности:", ln=1)
            pdf.image(self.graph_file, x=15, w=180)
        
        timestamp = datetime.now().strftime('%d.%m.%Y_%H-%M')
        filename = os.path.join(self.reports_dir, f"Нагрузочный_тест_{timestamp}.pdf")
        pdf.output(filename)
        print(f"✅ Отчёт сохранён: {filename}")
        return filename
    
    def cleanup(self):
        files = [self.graph_file,
                 f"{self.csv_prefix}_stats.csv",
                 f"{self.csv_prefix}_stats_history.csv",
                 f"{self.csv_prefix}_failures.csv",
                 f"{self.csv_prefix}_exceptions.csv"]
        for f in files:
            if os.path.exists(f):
                try: os.remove(f)
                except: pass
    
    def execute(self):
        try:
            self.check_locustfile()
            if not self.run_test():
                return False
            df = self.process_results()
            if df is None:
                return False
            self.create_graph(df)
            report = self.generate_report(df)
            self.cleanup()
            print(f"\n✅ Нагрузочное тестирование завершено. Отчёт: {report}")
            return True
        except Exception as e:
            print(f"\n❌ Ошибка: {e}")
            return False