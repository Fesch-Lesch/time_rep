from locust import HttpUser, task, between

class DnDUser(HttpUser):
    # Пауза между действиями от 1 до 3 секунд
    wait_time = between(1, 3)

    def on_start(self):
        """Выполняется при старте каждого пользователя (Авторизация)"""
        # Данные берем из вашего login.php [2]
        self.client.post("/dnd-site/login.php", {
            "username": "admin",
            "password": "password"
        })

    @task(3)
    def view_dashboard(self):
        """Просмотр главной панели (часто)"""
        self.client.get("/dnd-site/dashboard.php")

    @task(1)
    def view_bestiary(self):
        """Просмотр бестиария (реже)"""
        self.client.get("/dnd-site/public/bestiary-view.php")

    @task(1)
    def view_students(self):
        """Просмотр рейтинга (реже)"""
        self.client.get("/dnd-site/public/student-rating.php") 
