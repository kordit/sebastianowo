<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Babylon.js Game</title>
    <style>
        html,
        body {
            overflow: hidden;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        #renderCanvas {
            width: 100%;
            height: 80%;
            /* Adjust canvas height */
            touch-action: none;
        }

        #gameLog {
            width: 100%;
            height: 20%;
            /* Adjust log height */
            background-color: #000;
            color: #fff;
            padding: 10px;
            overflow-y: scroll;
            font-family: monospace;
            box-sizing: border-box;
            /* Include padding in width/height */
        }

        #damageOverlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            /* Allows clicking through the overlay */
            background: transparent;
            opacity: 0;
            transition: opacity 0.5s;
        }
    </style>
    <script src="https://cdn.babylonjs.com/babylon.js"></script>
    <script src="https://cdn.babylonjs.com/gui/babylon.gui.min.js"></script>
    <script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
</head>

<body>
    <canvas id="renderCanvas"></canvas>
    <div id="gameLog"></div>
    <div id="damageOverlay"></div>
    <script>
        class Game {
            constructor(canvasId) {
                this.canvas = document.getElementById(canvasId);
                this.engine = new BABYLON.Engine(this.canvas, true, {
                    alpha: true
                });
                this.scene = new BABYLON.Scene(this.engine);
                this.scene.clearColor = new BABYLON.Color4(0, 0, 0, 0);
                this.hp1 = 100;
                this.hp2 = 100;
                this.power1 = 0;
                this.power2 = 0;
                this.turn = 1;
                this.phase = "attack";

                this.powerIncrease = null;
                this.lastDodgeTime = 0;
                this.dodgeWindow = 250; // 0.25 seconds in milliseconds
                this.createScene();
                this.createCharacters();
                this.createUI();
                this.run();
                this.log("Gra rozpoczęta!");
            }

            createScene() {
                this.camera = new BABYLON.ArcRotateCamera("Camera", 0, 0, 10, new BABYLON.Vector3(0, 0, 0), this.scene);
                this.camera.attachControl(this.canvas, true);
                this.light = new BABYLON.HemisphericLight("light", new BABYLON.Vector3(0, 1, 0), this.scene);
                this.light.intensity = 0.7;

            }

            createCharacters() {
                // Player 1
                this.player1 = BABYLON.MeshBuilder.CreateCapsule("player1", {
                    height: 2,
                    radius: 0.5,
                    tessellation: 32
                }, this.scene);
                this.player1.position = new BABYLON.Vector3(-3, 1, 0);
                this.player1.material = new BABYLON.StandardMaterial("player1Mat", this.scene);
                this.player1.material.diffuseColor = new BABYLON.Color3(1, 0, 0); // Red

                // Player 2 (Enemy)
                this.player2 = BABYLON.MeshBuilder.CreateCapsule("player2", {
                    height: 2,
                    radius: 0.5,
                    tessellation: 32
                }, this.scene);
                this.player2.position = new BABYLON.Vector3(3, 1, 0);
                this.player2.material = new BABYLON.StandardMaterial("player2Mat", this.scene);
                this.player2.material.diffuseColor = new BABYLON.Color3(0, 0, 1); // Blue
            }

            createUI() {
                this.advancedTexture = BABYLON.GUI.AdvancedDynamicTexture.CreateFullscreenUI("UI");
                this.createBars();
                this.resetBars();
                this.createActionButtons();
            }

            createBars() {
                this.hpBar1 = this.createBar("red", "-34%", "-45%");
                this.hpBar2 = this.createBar("blue", "34%", "-45%");
                this.powerBar1 = this.createBar("green", "-34%", "45%");
                this.powerBar2 = this.createBar("orange", "34%", "45%");
            }

            createBar(color, left, top) {
                const barContainer = new BABYLON.GUI.Rectangle();
                barContainer.width = "30%";
                barContainer.height = "5%";
                barContainer.color = "white";
                barContainer.background = "gray";
                barContainer.left = left;
                barContainer.top = top;
                this.advancedTexture.addControl(barContainer);

                const bar = new BABYLON.GUI.Rectangle();
                bar.width = "100%";
                bar.height = "100%";
                bar.color = "white";
                bar.background = color;
                barContainer.addControl(bar);

                return bar;
            }

            createActionButtons() {
                this.attackButton = BABYLON.GUI.Button.CreateSimpleButton("attackBtn", "Atak");
                this.attackButton.width = "200px";
                this.attackButton.height = "60px";
                this.attackButton.color = "white";
                this.attackButton.background = "black";
                this.attackButton.top = "40%";
                this.attackButton.onPointerDownObservable.add(() => this.chargeAttack());
                this.attackButton.onPointerUpObservable.add(() => {
                    this.nextPhase();
                    this.lastDodgeTime = 0;
                });

                this.dodgeButton = BABYLON.GUI.Button.CreateSimpleButton("dodgeBtn", "Unik");
                this.dodgeButton.width = "200px";
                this.dodgeButton.height = "60px";
                this.dodgeButton.color = "white";
                this.dodgeButton.background = "blue";
                this.dodgeButton.top = "40%";
                this.dodgeButton.isVisible = false; // Initially hidden
                this.dodgeButton.onPointerDownObservable.add(() => this.attemptDodge());

                this.advancedTexture.addControl(this.attackButton);
                this.advancedTexture.addControl(this.dodgeButton);
                this.toggleButtons(); // Set initial button visibility based on phase
            }
            chargeAttack() {
                this.powerIncrease = setInterval(() => {
                    if (this.turn === 1) {
                        this.power1 = Math.min(100, this.power1 + 2);
                        this.powerBar1.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
                        this.powerBar1.width = `${this.power1}%`;
                    }
                }, 100);
            }

            nextPhase() {
                clearInterval(this.powerIncrease);
                if (this.phase === "attack") {
                    this.log("Gracz atakuje przeciwnika! Moc ataku: " + this.power1);
                    this.log("Aktualne HP przeciwnika: " + this.hp2);
                    this.animateAttack(this.player1, this.player2); // Animate player 1's attack
                    const damage = 10 + Math.floor(this.power1 / 10);
                    this.hp2 = Math.max(0, this.hp2 - damage);
                    this.power1 = 0;
                    this.powerBar1.width = "0%";
                    this.phase = "defense";
                    this.toggleButtons();
                    this.updateBars();

                    if (this.hp2 <= 0) {
                        this.endGame("Gracz wygrał!");
                        return;
                    }

                    setTimeout(() => {
                        this.enemyTurn();
                    }, 2000);
                }
                this.updateBars();
            }

            toggleButtons() {
                if (this.phase === "attack") {
                    this.attackButton.isVisible = true;
                    this.dodgeButton.isVisible = false;
                } else {
                    this.attackButton.isVisible = false;
                    this.dodgeButton.isVisible = true;
                }
            }

            enemyTurn() {
                if (this.hp1 <= 0 || this.hp2 <= 0) {
                    return; // Prevent further actions if the game is over
                }
                this.log("Przeciwnik ładuje atak!");
                this.log("Moc ataku przeciwnika: " + this.power2);
                let enemyPower = Math.floor(Math.random() * 100);
                this.powerBar2.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
                let enemyPowerLoad = setInterval(() => {
                    this.power2 = Math.min(100, this.power2 + 2);
                    this.powerBar2.width = `${this.power2}%`;
                    if (this.power2 >= enemyPower) clearInterval(enemyPowerLoad);
                }, 100);

                setTimeout(() => {
                    clearInterval(enemyPowerLoad); // Ensure interval is cleared
                    this.power2 = 0; // Reset enemy power
                    this.powerBar2.width = "0%";

                    // Decide if enemy will dodge
                    const shouldDodge = Math.random() < 0.3; // 30% chance to dodge
                    if (shouldDodge) {
                        this.log("Przeciwnik próbuje uniku!");
                        this.animateDodge(this.player2);
                        setTimeout(() => {
                            this.phase = "attack";
                            this.toggleButtons();
                        }, 1000); // Short delay for dodge animation
                    } else {
                        this.animateAttack(this.player2, this.player1);

                        setTimeout(() => {
                            const currentTime = performance.now();
                            const dodgeTiming = currentTime - this.lastDodgeTime;

                            if (dodgeTiming <= this.dodgeWindow && this.phase === "attack") {
                                this.log("Gracz uniknął ataku!");
                                this.showDamageOverlay("blue"); // Blue for dodge
                            } else {
                                this.log("Przeciwnik atakuje! Moc ataku: " + enemyPower);
                                this.log("Aktualne HP gracza: " + this.hp1);
                                const damage = 10 + Math.floor(enemyPower / 10);
                                this.hp1 = Math.max(0, this.hp1 - damage);
                                this.showDamageOverlay("red"); // Red for hit

                                if (dodgeTiming > this.dodgeWindow) {
                                    this.log("Unik był za późno! " + dodgeTiming + "ms");
                                }
                            }

                            this.updateBars();
                            this.phase = "attack";
                            this.toggleButtons();

                            if (this.hp1 <= 0) {
                                this.endGame("Przeciwnik wygrał!");
                                return;
                            }
                        }, 1000);
                    }
                }, 2000);
            }
            animateDodge(character) {
                // Simple dodge animation: move slightly to the side
                const initialPosition = character.position.clone();
                const dodgeDistance = 1; // Distance to dodge

                // Determine dodge direction (left or right)
                const dodgeDirection = Math.random() < 0.5 ? 1 : -1; // 50% chance for each direction
                const dodgePosition = initialPosition.add(new BABYLON.Vector3(dodgeDirection * dodgeDistance, 0, 0));

                // Animate the dodge
                BABYLON.Animation.CreateAndStartAnimation("dodgeMove", character, "position", 30, 15, initialPosition, dodgePosition, 0, null, () => {
                    // After dodging, return to the initial position
                    BABYLON.Animation.CreateAndStartAnimation("dodgeReturn", character, "position", 30, 15, character.position, initialPosition, 0);
                });
            }

            attemptDodge() {
                this.lastDodgeTime = performance.now();
                this.log("Gracz próbuje uniku!");
            }

            animateAttack(attacker, target) {
                // Simple animation: Move attacker towards target, then back
                const initialPosition = attacker.position.clone();
                const targetPosition = target.position.clone();
                const animationDistance = targetPosition.subtract(initialPosition).scale(0.8); // Move 80% of the way

                BABYLON.Animation.CreateAndStartAnimation("attackMove", attacker, "position", 30, 15, initialPosition, targetPosition.subtract(animationDistance), 0, null, () => {
                    BABYLON.Animation.CreateAndStartAnimation("attackReturn", attacker, "position", 30, 15, attacker.position, initialPosition, 0);
                });
            }

            showDamageOverlay(color) {
                const overlay = document.getElementById("damageOverlay");
                overlay.style.background = `radial-gradient(circle, ${color}, transparent)`;
                overlay.style.opacity = 0.5;

                setTimeout(() => {
                    overlay.style.opacity = 0;
                }, 500); // Duration of the effect
            }

            resetBars() {
                this.power2 = 0;
                if (this.powerBar2) this.powerBar2.width = "0%";
            }

            updateBars() {
                this.hpBar1.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_RIGHT;
                this.hpBar1.width = `${this.hp1}%`;
                this.hpBar2.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_RIGHT;
                this.hpBar2.width = `${this.hp2}%`;
            }
            log(message) {
                const logElement = document.getElementById("gameLog");
                logElement.innerHTML += message + "<br>";
                logElement.scrollTop = logElement.scrollHeight; // Auto-scroll to bottom
            }

            endGame(message) {
                this.log(message);
                this.attackButton.isVisible = false;
                this.dodgeButton.isVisible = false;
                this.phase = "ended";
            }


            run() {
                this.engine.runRenderLoop(() => this.scene.render());
                window.addEventListener("resize", () => this.engine.resize());
            }
        }

        new Game("renderCanvas");
    </script>
</body>

</html>