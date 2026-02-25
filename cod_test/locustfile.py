from locust import HttpUser, task, between

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
