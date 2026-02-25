import os
import time
import subprocess
from config import Config
from auth_tester import AuthTester
from load_tester import LoadTester

class DnDTester:
    def __init__(self):
        self.config = Config()  # теперь это объект с настройками
        
    def check_font(self):
        if not os.path.exists(self.config.FONT_PATH):
            print(f"⚠️  Шрифт {self.config.FONT_PATH} не найден. Отчёты могут отображаться некорректно.")
            return False
        return True
    
    def check_dependencies(self):
        missing = []
        try: import selenium
        except: missing.append("selenium")
        try: import pandas
        except: missing.append("pandas")
        try: import matplotlib
        except: missing.append("matplotlib")
        try: import fpdf
        except: missing.append("fpdf")
        try: subprocess.run(["locust", "--version"], capture_output=True)
        except: missing.append("locust")
        
        if missing:
            print("❌ Отсутствуют компоненты:", ", ".join(missing))
            print("Установите: pip install selenium pandas matplotlib fpdf locust")
            return False
        return True
    
    def show_menu(self):
        print("\n" + "="*60)
        print("🐉 DnD СИСТЕМА ТЕСТИРОВАНИЯ 🐉")
        print("="*60)
        print("  [1] 🔐 Тестирование авторизации")
        print("  [2] 🚀 Нагрузочное тестирование")
        print("  [3] 📊 Запустить оба")
        print("  [4] ⚙️  Настройки")
        print("  [0] ❌ Выход")
        print("-"*60)
    
    def settings_menu(self):
        while True:
            print("\n" + "="*60)
            print("⚙️  НАСТРОЙКИ ПРОГРАММЫ")
            print("="*60)
            print("Текущие параметры:")
            print(f"  1. Шрифт для PDF: {self.config.FONT_PATH}")
            print(f"  2. Папка для отчётов: {self.config.REPORTS_DIR}")
            print("\n--- Тестирование авторизации ---")
            print(f"  3. Файл конфигурации тестов: {self.config.AUTH_CONFIG_FILE}")
            print("\n--- Нагрузочное тестирование ---")
            print(f"  4. Файл сценария Locust: {self.config.LOAD_TEST_LOCUSTFILE}")
            print(f"  5. Целевой хост: {self.config.LOAD_TEST_HOST}")
            print(f"  6. Количество пользователей: {self.config.LOAD_TEST_USERS}")
            print(f"  7. Скорость прироста (польз/сек): {self.config.LOAD_TEST_SPAWN_RATE}")
            print(f"  8. Длительность теста: {self.config.LOAD_TEST_RUN_TIME}")
            print("\n  [s] Сохранить и вернуться")
            print("  [0] Вернуться без изменений")
            choice = input("\nВыберите пункт для изменения (или 0/s): ").strip()
            
            if choice == "0":
                break
            elif choice == "s":
                self.config.save_settings()
                break
            elif choice == "1":
                new_val = input(f"Введите новый путь к шрифту (Enter = '{self.config.FONT_PATH}'): ").strip()
                if new_val:
                    self.config.update_setting("FONT_PATH", new_val)
            elif choice == "2":
                new_val = input(f"Введите новую папку для отчётов (Enter = '{self.config.REPORTS_DIR}'): ").strip()
                if new_val:
                    self.config.update_setting("REPORTS_DIR", new_val)
                    os.makedirs(self.config.REPORTS_DIR, exist_ok=True)
            elif choice == "3":
                new_val = input(f"Введите путь к файлу конфигурации авторизации (Enter = '{self.config.AUTH_CONFIG_FILE}'): ").strip()
                if new_val:
                    self.config.update_setting("AUTH_CONFIG_FILE", new_val)
            elif choice == "4":
                new_val = input(f"Введите путь к файлу сценария Locust (Enter = '{self.config.LOAD_TEST_LOCUSTFILE}'): ").strip()
                if new_val:
                    self.config.update_setting("LOAD_TEST_LOCUSTFILE", new_val)
            elif choice == "5":
                new_val = input(f"Введите целевой хост (Enter = '{self.config.LOAD_TEST_HOST}'): ").strip()
                if new_val:
                    self.config.update_setting("LOAD_TEST_HOST", new_val)
            elif choice == "6":
                new_val = input(f"Введите количество пользователей (Enter = {self.config.LOAD_TEST_USERS}): ").strip()
                if new_val:
                    try:
                        self.config.update_setting("LOAD_TEST_USERS", int(new_val))
                    except:
                        print("❌ Должно быть число.")
            elif choice == "7":
                new_val = input(f"Введите скорость прироста (польз/сек) (Enter = {self.config.LOAD_TEST_SPAWN_RATE}): ").strip()
                if new_val:
                    try:
                        self.config.update_setting("LOAD_TEST_SPAWN_RATE", int(new_val))
                    except:
                        print("❌ Должно быть число.")
            elif choice == "8":
                new_val = input(f"Введите длительность теста (например, 30s) (Enter = '{self.config.LOAD_TEST_RUN_TIME}'): ").strip()
                if new_val:
                    self.config.update_setting("LOAD_TEST_RUN_TIME", new_val)
            else:
                print("❌ Неверный пункт.")
    
    def get_auth_params(self):
        """Возвращает параметры для теста авторизации из настроек (можно переопределить)."""
        print("\n--- Тестирование авторизации ---")
        use_saved = input("Использовать сохранённые настройки? (y/n, Enter = да): ").strip().lower()
        if use_saved == 'n':
            config_file = input(f"Введите путь к файлу конфигурации (Enter = '{self.config.AUTH_CONFIG_FILE}'): ").strip()
            if config_file == "":
                config_file = self.config.AUTH_CONFIG_FILE
            reports_dir = input(f"Введите папку для отчётов (Enter = '{self.config.REPORTS_DIR}'): ").strip()
            if reports_dir == "":
                reports_dir = self.config.REPORTS_DIR
            return {'config_file': config_file, 'reports_dir': reports_dir}
        else:
            return {'config_file': self.config.AUTH_CONFIG_FILE, 'reports_dir': self.config.REPORTS_DIR}
    
    def get_load_params(self):
        """Возвращает параметры для нагрузочного теста из настроек (можно переопределить)."""
        print("\n--- Нагрузочное тестирование ---")
        use_saved = input("Использовать сохранённые настройки? (y/n, Enter = да): ").strip().lower()
        if use_saved == 'n':
            locustfile = input(f"Введите путь к файлу сценария Locust (Enter = '{self.config.LOAD_TEST_LOCUSTFILE}'): ").strip()
            if locustfile == "":
                locustfile = self.config.LOAD_TEST_LOCUSTFILE
            host = input(f"Введите целевой хост (Enter = '{self.config.LOAD_TEST_HOST}'): ").strip()
            if host == "":
                host = self.config.LOAD_TEST_HOST
            users = input(f"Введите количество пользователей (Enter = {self.config.LOAD_TEST_USERS}): ").strip()
            if users == "":
                users = self.config.LOAD_TEST_USERS
            else:
                try:
                    users = int(users)
                except:
                    users = self.config.LOAD_TEST_USERS
            spawn_rate = input(f"Введите скорость прироста (польз/сек, Enter = {self.config.LOAD_TEST_SPAWN_RATE}): ").strip()
            if spawn_rate == "":
                spawn_rate = self.config.LOAD_TEST_SPAWN_RATE
            else:
                try:
                    spawn_rate = int(spawn_rate)
                except:
                    spawn_rate = self.config.LOAD_TEST_SPAWN_RATE
            run_time = input(f"Введите длительность теста (например, 30s, Enter = '{self.config.LOAD_TEST_RUN_TIME}'): ").strip()
            if run_time == "":
                run_time = self.config.LOAD_TEST_RUN_TIME
            reports_dir = input(f"Введите папку для отчётов (Enter = '{self.config.REPORTS_DIR}'): ").strip()
            if reports_dir == "":
                reports_dir = self.config.REPORTS_DIR
            return {
                'locustfile': locustfile,
                'host': host,
                'users': users,
                'spawn_rate': spawn_rate,
                'run_time': run_time,
                'reports_dir': reports_dir
            }
        else:
            return {
                'locustfile': self.config.LOAD_TEST_LOCUSTFILE,
                'host': self.config.LOAD_TEST_HOST,
                'users': self.config.LOAD_TEST_USERS,
                'spawn_rate': self.config.LOAD_TEST_SPAWN_RATE,
                'run_time': self.config.LOAD_TEST_RUN_TIME,
                'reports_dir': self.config.REPORTS_DIR
            }
    
    def run(self):
        print("🐉 Добро пожаловать!")
        self.check_font()
        if not self.check_dependencies():
            input("Нажмите Enter для продолжения...")
        
        while True:
            self.show_menu()
            choice = input("Ваш выбор: ").strip()
            if choice == "1":
                params = self.get_auth_params()
                tester = AuthTester(**params)
                tester.execute()
                input("Нажмите Enter...")
            elif choice == "2":
                params = self.get_load_params()
                tester = LoadTester(**params)
                tester.execute()
                input("Нажмите Enter...")
            elif choice == "3":
                print("\n🔄 Запуск обоих тестов...")
                auth_params = self.get_auth_params()
                a_tester = AuthTester(**auth_params)
                a_ok = a_tester.execute()
                print("\n" + "="*60)
                load_params = self.get_load_params()
                l_tester = LoadTester(**load_params)
                l_ok = l_tester.execute()
                print("\n📊 Итоги: Авторизация {} | Нагрузка {}".format("✅" if a_ok else "❌", "✅" if l_ok else "❌"))
                input("Нажмите Enter...")
            elif choice == "4":
                self.settings_menu()
            elif choice == "0":
                print("👋 До свидания!")
                break
            else:
                print("❌ Неверный выбор")
                time.sleep(1)

if __name__ == "__main__":
    try:
        DnDTester().run()
    except KeyboardInterrupt:
        print("\n👋 Программа прервана")
    except Exception as e:
        print(f"\n❌ Ошибка: {e}")
        import traceback
        traceback.print_exc()
        input("Нажмите Enter...")