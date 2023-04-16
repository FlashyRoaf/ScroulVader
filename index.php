<?php
error_reporting(0);
session_start();
$highestScore = $_POST['highestScore'];

if ($highestScore >= $_SESSION['prevHighestScore']) {
    $_SESSION['prevHighestScore'] = $highestScore;
    setcookie("highest_score", $highestScore, time() + 3600, "/");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Invader</title>
    <style>
        body {
            margin: 0;
            outline: 0;
            background-color: black;
        }

        #score {
            position: fixed;
            color: white;
            font-family: sans-serif;
            font-size: 2rem;
            bottom: 0;
            right: 0;
            margin: 1.5rem;
        }

        #highestScore {
            opacity: 0;
            position: absolute;
            transition: opacity 15s;
            color: white;
            font-size: 2rem;
            font-family: sans-serif;
            left: 42%;
            bottom: 25%;
        }

        .attribution {
            opacity: 0;
            position: fixed;
            color: white;
            bottom: 0;
            left: 0;
        }
    </style>
</head>
<body>
    <?php echo '<span id="score">0</span>'; ?>
    <?php echo '<span id="highestScore">Highest Score: 0</span>'; ?>
    <div class="attribution">
        <span>Image: https://id.pinterest.com/pin/632052128972624092/</span><br>
        <span>Music: https://tobyfox.bandcamp.com/album/undertale-soundtrack</span><br>
        <span>Sound: https://www.myinstants.com/en/instant/you-died/?utm_source=copy&utm_medium=share</span>
    </div>
    <canvas></canvas>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const canvas = document.querySelector("canvas");
        const scoreEl = document.querySelector("#score");
        const highestScoreEl = document.querySelector("#highestScore");
        const attributionEl = document.querySelector(".attribution")
        const c = canvas.getContext("2d");

        // const imageBackground = new Image();
        // imageBackground.src = "./assets/space.jpg";

        canvas.width = innerWidth;
        canvas.height = innerHeight - 4.01;
        // canvas.height = innerHeight;

        const scaling = canvas.width / canvas.height;

        let wPressed = false;
        let sPressed = false;
        let dPressed = false;
        let aPressed = false;
        let jPressed = false;

        let gameOver = false;

        let modeKentang = true;
        let modeSuperKentang = false;

        class MakeText {
            constructor({word, font, position, faded}) {
                this.word = word;
                this.font = font;
                this.position = position;

                this.opacity = 1;
            }

            blit() {
                c.font = this.font;
                c.fillText(this.word, this.position.x, this.position.y);
            }
        }
        
        class GameOverScreen {
            constructor() {
                const image = new Image();
                image.src = "./assets/youdied.jpeg";
                image.onload = () => {
                    this.scaling = 0.35;
                    this.image = image
                    this.imageWidth = image.width * this.scaling;
                    this.imageHeight = image.height * this.scaling;
                    this.position = {
                        x: 0,
                        y: 0,
                    }
                }
                
                this.audio = new Audio("./sound/dark-souls-_you-died_-sound-effect-from-youtube.mp3");
                this.opacity = 0;
            }

            blit() {
                if (this.image) {
                    c.save()
                    c.globalAlpha = this.opacity;
                    c.drawImage(this.image, this.position.x, this.position.y, canvas.width, canvas.height)
                    c.restore()
                }
            }
        }

        class Player {
            constructor() {
                const image = new Image();
                image.src = "./assets/ship.png";
                image.onload = () => {
                    this.scaling = 0.5;
                    // this.scaling = scaling * 0.23
                    this.image = image;
                    this.imageWidth = image.width * this.scaling;
                    this.imageHeight = image.height * this.scaling;
                    this.position = {
                        x: canvas.width / 2 - this.imageWidth / 2,
                        y: canvas.height - this.imageHeight - 30,
                    }
                    this.rectPosition = {
                        x: this.position.x + 30 * this.scaling,
                        y: this.position.y + 20 * this.scaling,
                    }
                    this.rectWidth = 20 * this.scaling;
                    this.rectHeight = 50 * this.scaling;
                }

                this.velocity = {
                    x: 0,
                    y: 0,
                }

                this.rotation = 0;
                this.opacity = 1;
                this.health = 3;
            }

            
            blit() {
                c.save();
                c.globalAlpha = this.opacity;
                c.translate(player.position.x + player.imageWidth / 2, player.position.y + player.imageHeight / 2);
                c.rotate(this.rotation);
                c.translate(-player.position.x - player.imageWidth / 2, -player.position.y - player.imageHeight / 2);
                
                c.fillStyle = "blue";
                c.fillRect(this.rectPosition.x, this.rectPosition.y, this.rectWidth, this.rectHeight);
                c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);

                c.restore();
            }

            update() {
                if (this.image) {
                    this.blit();
                    this.position.x += this.velocity.x
                    this.position.y += this.velocity.y
                    this.rectPosition.x += this.velocity.x
                    this.rectPosition.y += this.velocity.y
                }
            }
        }

        class HealthUI {
            constructor({position}) {
                const image = new Image()
                image.src = "./assets/heart.png";
                image.onload = () => {
                    this.image = image;
                    this.scaling = 0.35;
                    this.imageWidth = image.width * this.scaling;
                    this.imageHeight=  image.height * this.scaling;
                    this.position = {
                        x: position.x,
                        y: position.y,
                    }
                }
            }

            blit() {
                if (this.image) {
                    c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);
                }
            }
        }

        class Projectile {
            constructor(position, velocity) {
                this.position = position;
                this.velocity = velocity;
                this.radius = 18;

                // const image = new Image();
                // image.src = "./assets/projectile1.png";
                // image.onload = () => {
                this.scaling = 0.35
                //     this.image = image;
                //     this.imageWidth = image.width * this.scaling;
                //     this.imageHeight = image.height * this.scaling;
                // }

            }

            blit() {
                c.beginPath();
                c.arc(this.position.x + 40 * this.scaling, this.position.y + 45 * this.scaling, this.radius * this.scaling, 0, Math.PI * 2);
                c.fillStyle = "blue";
                // c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);
                c.fill();
                c.closePath();
            }

            update() {
                // if (this.image) {
                //     this.blit();
                //     this.position.x += this.velocity.x;
                //     this.position.y += this.velocity.y;
                this.blit();
                this.position.x += this.velocity.x;
                this.position.y += this.velocity.y;

                // }
            }
        }

        class EnemyProjectile {
            constructor({position, velocity}) {
                this.position = position;
                this.velocity = velocity;
                this.radius = 18;
                this.width = 15;
                this.height = 70;

                // const image = new Image();
                // image.src = "./assets/projectile1.png";
                // image.onload = () => {
                this.scaling = 0.35;
                //     this.image = image;
                //     this.imageWidth = image.width * this.scaling;
                //     this.imageHeight = image.height * this.scaling;
                // }

            }

            blit() {
                c.fillStyle = "red";
                c.fillRect(this.position.x, this.position.y, this.width * this.scaling, this.height * this.scaling)
                
                // c.beginPath();
                // c.arc(this.position.x, this.position.y, this.radius * this.scaling, 0, Math.PI * 2);
                // c.fillStyle = "red";
                // // c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);
                // c.fill();
                // c.closePath();
            }
            
            update() {
                // if (this.image) {
                //     this.blit();
                //     this.position.x += this.velocity.x;
                //     this.position.y += this.velocity.y;
                this.blit();
                this.position.x += this.velocity.x;
                this.position.y += this.velocity.y;

                // }
            }
        }

        class Particle {
            constructor({position, velocity, radius, particleColor}) {
                this.position = position;
                this.velocity = velocity;
                this.radius = radius;
                this.particleColor = particleColor

                // const image = new Image();
                // image.src = "./assets/projectile1.png";
                // image.onload = () => {
                this.scaling = 0.35
                this.opacity = 1;
                //     this.image = image;
                //     this.imageWidth = image.width * this.scaling;
                //     this.imageHeight = image.height * this.scaling;
                // }

            }

            blit() {
                if (modeKentang) {
                    c.save()
                    c.globalAlpha = this.opacity;
                    c.beginPath();
                    c.arc(this.position.x + 40 * this.scaling, this.position.y + 45 * this.scaling, this.radius * this.scaling, 0, Math.PI * 2);
                    c.fillStyle = this.particleColor;
                    // c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);
                    c.fill();
                    c.closePath();
                    c.restore()
                } else {
                    c.beginPath();
                    c.arc(this.position.x + 40 * this.scaling, this.position.y + 45 * this.scaling, this.radius * this.scaling, 0, Math.PI * 2);
                    c.fillStyle = this.particleColor;
                    // c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);
                    c.fill();
                    c.closePath();
                }
            }

            update() {
                // if (this.image) {
                //     this.blit();
                //     this.position.x += this.velocity.x;
                //     this.position.y += this.velocity.y;
                this.blit();
                this.position.x += this.velocity.x;
                this.position.y += this.velocity.y;

                if (modeKentang) this.opacity -= 0.008;
                // }
            }
        }

        class Enemy {
            constructor({position}) {
                const image = new Image();
                image.src = "./assets/enemy1.png";
                image.onload = () => {
                    this.scaling = 0.35;
                    this.image = image;
                    this.imageWidth = image.width * this.scaling;
                    this.imageHeight = image.height * this.scaling;
                    this.position = {
                        x: position.x,
                        y: position.y,
                    }
                    this.rectPosition = {
                        x: this.position.x + 13 * this.scaling,
                        y: this.position.y + 20 * this.scaling,
                    }
                    this.rectWidth = 55 * this.scaling;
                    this.rectHeight = 50 * this.scaling;
                }

                this.velocity = {
                    x: 0,
                    y: 0,
                }

                this.rotation = 0
            }

            
            blit() {
                // c.save();
                // c.translate(player.position.x + player.imageWidth / 2, player.position.y + player.imageHeight / 2);
                // c.rotate(this.rotation);
                // c.translate(-player.position.x - player.imageWidth / 2, -player.position.y - player.imageHeight / 2);
                
                // c.fillStyle = "black";
                // c.fillRect(this.rectPosition.x, this.rectPosition.y, this.rectWidth, this.rectHeight);
                c.drawImage(this.image, this.position.x, this.position.y, this.imageWidth, this.imageHeight);

                // c.restore();
            }

            shoot(enemyProjectiles) {
                enemyProjectiles.push(new EnemyProjectile({
                    position: {
                        x: this.position.x + this.imageWidth / 2,
                        y: this.position.y + this.imageHeight
                    },

                    velocity: {
                        x: 0,
                        y: 12 * this.scaling,
                    },
                }))
            }

            update({velocity}) {
                if (this.image) {
                    this.blit();
                    this.position.x += velocity.x;
                    this.position.y += velocity.y;
                    this.rectPosition.x += velocity.x;
                    this.rectPosition.y += velocity.y;
                }
            }
        }

        class Grid {
            constructor() {
                this.position = {
                    x: 0,
                    y: 0,
                }

                this.scaling = 0.35
                this.speed = 2.5;
                this.velocity = {
                    x: this.speed * this.scaling,
                    y: 0.05 * this.scaling,
                }

                this.enemys = [];

                const columns = Math.floor(Math.random() * 10 + 5)
                const rows = Math.floor(Math.random() * 5 + 2)

                this.width = columns * 30;
                
                for (let i = 0; i < columns; i++) {
                    for (let j = 0; j < rows; j++) {
                        this.enemys.push(new Enemy({position: {
                            x: i * 40,
                            y: j * 40,
                        }}));
                    }
                }
                // console.log(this.enemys)
            }

            update() {
                this.position.x += this.velocity.x;
                this.position.y += this.velocity.y;

                if (this.position.x + this.width * 1.28>= canvas.width) {
                    this.velocity.x = -this.speed * this.scaling;
                }
                if (this.position.x <= 0) {
                    this.velocity.x = this.speed * this.scaling;
                }
            }
        }

        const player = new Player();
        let originalSpeed = 2;
        let speed = originalSpeed;
        let shootDelay = 0;
        let immunityFrame = 0;
        let score = 0;
        // let highestScore = 0 + <?php echo $_COOKIE["highest_score"] ?>;
        let highestScore = <?php echo isset($_COOKIE["highest_score"]) ? $_COOKIE["highest_score"] : 0 ?>;

        
        const gameOverScreen = new GameOverScreen();
        const highestScoreText = new MakeText({
            word: "Halo",
            font: "30px serif",
            position: {
                x: 0,
                y: 0,
            },
            faded: false,
        });

        const grids = []
        const projectiles = [];
        const enemyProjectiles = [];
        const particles = [];
        const healthUIs = [];

        let frames = 0;
        let randomNum = Math.floor(Math.random() * 500 + 800);

        const music = new Audio("./music/enemy-approaching-toby-fox.mp3");
        let playGameOverSound = true;
        
        for (i = 0; i < player.health; i++) {
            healthUIs.push(new HealthUI({
                position: {
                    x: i * 40 + 20,
                    y: 20,
                }
            }))
        }

        function run() {
            requestAnimationFrame(run);
            music.volume = 0.6;
            music.play()
            shootDelay -= 0.1;
            immunityFrame -= 0.1;

            if (score > highestScore) {
                highestScore = score;
            }
            highestScoreEl.innerHTML = "Highest Score: " + highestScore
            // console.log(highestScore)
            
            $.ajax({
                url: "./index.php",
                method: "POST",
                data: {highestScore: highestScore},
                success: function(response) {
                    // console.log(response);
                },
                error: function() {
                    console.log("Error sending variable");
                }
            });
            c.fillStyle = "black";

            if (!modeSuperKentang) {
                for (i = 0; i < 2; i++) {
                    particles.push(new Particle({
                        position: {
                            x: Math.random() * canvas.width,
                            y: Math.random() * canvas.height,
                        },

                        velocity: {
                            x: 0,
                            y: 2,
                        },

                        radius: Math.random() * 5,
                        particleColor: "white",
                    }))
                }
            }

            // c.drawImage(imageBackground, 0, 0, canvas.width, canvas.height)
            c.fillRect(0, 0, canvas.width, canvas.height);
            
            player.update();
            

            if (player.health <= 0) {
                player.opacity = 0;
                gameOver = true;
            }

            particles.forEach((particle, index) => {
                if (particle.opacity <= 0) {
                    setTimeout(() => {
                        particles.splice(index, 1);
                    }, 0)
                } else {
                    particle.update();
                }
            })
            
            grids.forEach((grid, gridIndex) => {
                grid.update()

                if (frames % 100 === 0 && grid.enemys.length > 0) {
                    grid.enemys[Math.floor(Math.random() * grid.enemys.length)].shoot(enemyProjectiles)
                }
                
                grid.enemys.forEach((enemy, i) => {
                    enemy.update({velocity: grid.velocity})

                    projectiles.forEach((projectile, j) => {
                        if (projectile.position.y - projectile.radius <= enemy.rectPosition.y + enemy.rectHeight &&
                        projectile.position.x + projectile.radius >= enemy.rectPosition.x &&
                        projectile.position.x - projectile.radius <= enemy.rectPosition.x + enemy.rectWidth &&
                        projectile.position.y + projectile.radius >= enemy.rectPosition.y) {
                            setTimeout(() => {
                                const enemyFound = grid.enemys.find(
                                    (enemy2) => enemy2 === enemy
                                )

                                const projectileFound = projectiles.find(
                                    (projectile2) => projectile2 === projectile
                                )

                                if (enemyFound && projectileFound) {
                                    score += 100;
                                    scoreEl.innerHTML = score;
                                    grid.enemys.splice(i , 1);
                                    projectiles.splice(j, 1);
                                    // console.log("hit")

                                    for (i = 0; i < 15; i++) {
                                        particles.push(new Particle({
                                        position: {
                                            x: enemy.position.x + enemy.imageWidth / 2,
                                            y: enemy.position.y + enemy.imageHeight / 2,
                                        },
                                    
                                        velocity: {
                                            x: Math.random() - 0.5,
                                            y: Math.random() - 0.5,
                                        }, 

                                        radius: Math.random() * 3,
                                        particleColor: "white",
                                    }))
                            }

                                    if (grid.enemys.length > 0) {
                                        const firstEnemy = grid.enemys[0]
                                        const lastEnemy = grid.enemys[grid.enemys.length - 1]

                                        grid.width = lastEnemy.rectPosition.x - firstEnemy.rectPosition.x + lastEnemy.rectWidth
                                        grid.position.x = firstEnemy.rectPosition.x
                                    } else {
                                        grids.splice(gridIndex , 1)
                                    }
                                }
                            }, 0)

                            
                        }
                    })
                })
            });

            enemyProjectiles.forEach((enemyProjectile, index) => {
                if (enemyProjectile.position.y - enemyProjectile.height >= canvas.height) {
                    setTimeout(() => {
                        enemyProjectiles.splice(index, 1)
                    }, 0)
                } else {
                    enemyProjectile.update()
                }

                if (enemyProjectile.position.y + enemyProjectile.height >= player.rectPosition.y + player.rectHeight  &&
                enemyProjectile.position.x + enemyProjectile.width >= player.rectPosition.x &&
                enemyProjectile.position.x <= player.rectPosition.x + player.rectWidth &&
                enemyProjectile.position.y - enemyProjectile.height <= player.rectPosition.y - player.rectHeight) {
                    if (immunityFrame <= 0) {
                        for (i = 0; i < 15; i++) {
                            particles.push(new Particle({
                                position: {
                                    x: player.position.x + player.imageWidth / 2,
                                    y: player.position.y + player.imageHeight / 2,
                                },
    
                                velocity: {
                                    x: Math.random() - 0.5,
                                    y: Math.random() - 0.5,
                                },
    
                                radius: Math.random() * 20,
                                particleColor: "white",
                            }))
                        }

                        player.health -= 1;
                        
                        enemyProjectiles.splice(index, 1);
                        
                        immunityFrame = 2;
                    }
                }
            })
            
            projectiles.forEach((projectile, index) => {
                if (projectile.position.y + projectile.radius <= 0) {
                    setTimeout(() => {
                        projectiles.splice(index, 1);
                    }, 0)
                } else {
                    projectile.update();
                }
            })

            
            if (jPressed) {
                speed = originalSpeed / 2; 
            } else {
                speed = originalSpeed;
            }

            if (aPressed && player.position.x >= 0) {
                player.velocity.x = -speed;
                player.rotation = -0.15;
            } 
            else if (dPressed && player.position.x <= canvas.width - player.imageWidth) {
                player.velocity.x = speed;
                player.rotation = 0.15;
            }
            else {
                player.velocity.x = 0;
                player.rotation = 0;
            }
            if (wPressed && player.position.y >= 0) {
                player.velocity.y = -speed;
            }
            else if (sPressed && player.position.y <= canvas.height - player.imageHeight) {
                player.velocity.y = speed;
            }
            else {
                player.velocity.y = 0;
            }

            if (jPressed) {
                if (shootDelay <= 0) {
                    projectiles.push(new Projectile(
                        position = {
                            x: player.position.x + player.imageWidth / 2 - 14,
                            y: player.position.y,
                        },
                            
                        velocity = {
                            x: 0,
                            y: -10 * player.scaling,
                        }
                    ));
                    shootDelay = 2;
                }
            }

            if (frames % randomNum === 0) {
                grids.push(new Grid());
                randomNum = Math.floor(Math.random() * 500 + 800);
                frames = 0;
            }

            frames++

            healthUIs.forEach((healthUI) => {
                if (healthUIs.length > player.health) {
                    healthUIs.splice(-1, 1)
                }
                
                healthUI.blit();
            })
            
            if (gameOver) {
                gameOverScreen.blit()
                gameOverScreen.opacity += 0.001;
                highestScoreEl.style.opacity = 1;
                attributionEl.style.opacity = 1;
                setTimeout(() => {
                    playGameOverSound = false
                }, 6000)
                if (playGameOverSound) {
                    setTimeout(() => {
                        gameOverScreen.audio.loop = false;
                        gameOverScreen.audio.play();
                    }, 2500)
                }
                // c.font = highestScoreText.font;
                // c.fillText(highestScoreText.word, highestScoreText.position.x, highestScoreText.position.y);
            }
        }

        run();

        addEventListener("keydown" ,({key}) => {
            if (player.health > 0)
            switch (key) {
                case "a":
                    aPressed = true;
                    break;
                case "d":
                    dPressed = true;
                    break;
                case "w":
                    wPressed = true;
                    break;
                case "s":
                    sPressed = true;
                    break;
                case "j":
                    jPressed = true;
                    break;
            }
        })

        addEventListener("keyup" ,({key}) => {
            switch (key) {
                case "a":
                    aPressed = false;
                    break;
                case "d":
                    dPressed = false;
                    break;
                case "w":
                    wPressed = false;
                    break;
                case "s":
                    sPressed = false;
                    break;   
                case "j":
                    jPressed = false;
                    break;
            }
        })

    </script>
</body>
</html>