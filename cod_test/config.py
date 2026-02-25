import os
import json

class Config:
    # Файл с настройками приложения
    SETTINGS_FILE = "app_settings.json"
    
    # Значения по умолчанию
    DEFAULTS = {
        # Общие
        "FONT_PATH": "times.ttf",
        "REPORTS_DIR": "Отчеты",
        
        # Нагрузочное тестирование
        "LOAD_TEST_HOST": "http://localhost:3000",
        "LOAD_TEST_USERS": 50,
        "LOAD_TEST_SPAWN_RATE": 5,
        "LOAD_TEST_RUN_TIME": "20s",
        "LOAD_TEST_CSV_PREFIX": "load_test_data",
        "LOAD_TEST_LOCUSTFILE": "locustfile.py",
        
        # Тестирование авторизации
        "AUTH_CONFIG_FILE": "test_data.json",
        "AUTH_TEMP_SCREENS": "temp_screens"
    }
    
    def __init__(self):
        self.settings = self.load_settings()
        # Применяем настройки как атрибуты класса для удобства
        for key, value in self.settings.items():
            setattr(self, key, value)
        self.ensure_dirs()
    
    def load_settings(self):
        """Загружает настройки из файла или возвращает значения по умолчанию."""
        if os.path.exists(self.SETTINGS_FILE):
            try:
                with open(self.SETTINGS_FILE, 'r', encoding='utf-8') as f:
                    settings = json.load(f)
                # Обновляем значениями по умолчанию, если каких-то ключей нет
                for key, value in self.DEFAULTS.items():
                    if key not in settings:
                        settings[key] = value
                return settings
            except Exception as e:
                print(f"⚠️ Ошибка загрузки настроек: {e}. Используются значения по умолчанию.")
        return self.DEFAULTS.copy()
    
    def save_settings(self):
        """Сохраняет текущие настройки в файл."""
        try:
            with open(self.SETTINGS_FILE, 'w', encoding='utf-8') as f:
                json.dump(self.settings, f, ensure_ascii=False, indent=2)
            print("✅ Настройки сохранены.")
        except Exception as e:
            print(f"❌ Ошибка сохранения настроек: {e}")
    
    def update_setting(self, key, value):
        """Обновляет конкретную настройку и сохраняет файл."""
        self.settings[key] = value
        setattr(self, key, value)
        self.save_settings()
    
    def ensure_dirs(self):
        """Создаёт необходимые директории."""
        os.makedirs(self.REPORTS_DIR, exist_ok=True)
        # Папка для временных скриншотов создаётся при каждом тесте
    
    @staticmethod
    def resolve_path(path):
        """Преобразует относительный путь в абсолютный и проверяет, что это файл (не папка)"""
        if not path:
            return None
        abs_path = os.path.abspath(path)
        if os.path.isdir(abs_path):
            print(f"⚠️ Указанный путь '{path}' является папкой, а не файлом.")
            return None
        return abs_path