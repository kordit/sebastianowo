class Game {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.engine = new BABYLON.Engine(this.canvas, true);
        this.scene = new BABYLON.Scene(this.engine);
        this.hp1 = 100;
        this.hp2 = 100;
        this.power1 = 0;
        this.power2 = 0;
        this.turn = 1;
        this.phase = "attack";

        this.powerIncrease = null;
        this.lastDodgeTime = 0;
        this.createScene();
        this.createLayers();
        this.createUI();
        this.run();
    }

    createScene() {
        this.camera = new BABYLON.ArcRotateCamera("Camera", 0, 0, 10, new BABYLON.Vector3(0, 0, 0), this.scene);
        this.camera.attachControl(this.canvas, true);
    }

    createLayers() {
        this.backgroundLayer = new BABYLON.Layer("background", "/wp-content/themes/game/templates/walka/assets/background.jpg", this.scene, true);
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
        this.dodgeButton.isVisible = (this.phase === 'defense');
        this.dodgeButton.onPointerDownObservable.add(() => {
            console.log("Gracz wykonał unik!");
            console.log("Aktualne HP gracza: " + this.hp1);
            this.lastDodgeTime = performance.now();
            this.phase = "attack";
            this.toggleButtons();
            this.dodgeButton.isVisible = (this.phase === 'defense');
        });

        this.advancedTexture.addControl(this.attackButton);
        this.advancedTexture.addControl(this.dodgeButton);
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
            console.log("Gracz atakuje przeciwnika! Moc ataku: " + this.power1);
            console.log("Aktualne HP przeciwnika: " + this.hp2);
            const damage = 10 + Math.floor(this.power1 / 10);
            this.hp2 = Math.max(0, this.hp2 - damage);
            this.power1 = 0;
            this.powerBar1.width = "0%";
            this.phase = "defense";
            this.toggleButtons();
            this.lastDodgeTime = performance.now();
            setTimeout(() => {
                this.attackButton.isVisible = (this.phase === 'attack');
                this.dodgeButton.isVisible = false;
                this.dodgeButton.isVisible = false;
                this.enemyTurn();
                this.attackButton.isVisible = false;
            }, 1000);
        } else {
            console.log("Gracz wykonał unik!");
            this.lastDodgeTime = performance.now();
            this.phase = "attack";
            this.toggleButtons();
        }
        this.updateBars();
        console.log("Aktualizacja pasków: HP Gracza: " + this.hp1 + ", HP Przeciwnika: " + this.hp2);
    }

    toggleButtons() {
        this.attackButton.isVisible = !this.attackButton.isVisible;
        this.dodgeButton.isVisible = !this.dodgeButton.isVisible;
    }

    enemyTurn() {
        console.log("Przeciwnik ładuje atak!");
        console.log("Moc ataku przeciwnika: " + this.power2);
        let enemyPower = Math.floor(Math.random() * 100);
        this.powerBar2.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
        let enemyPowerLoad = setInterval(() => {
            this.power2 = Math.min(100, this.power2 + 2);
            this.powerBar2.width = `${this.power2}%`;
            if (this.power2 >= enemyPower) clearInterval(enemyPowerLoad);
        }, 100);
        this.powerBar2.width = `${this.power2}%`;
        this.power2 = 0;
        this.powerBar2.width = "0%";
        this.power2 = 0;
        this.dodgeButton.isVisible = false;
        setTimeout(() => {
            const currentTime = performance.now();
            if (currentTime - this.lastDodgeTime <= 1000) {
                console.log("Gracz uniknął ataku!");
            } else {
                console.log("Przeciwnik atakuje! Moc ataku: " + enemyPower);
                console.log("Aktualne HP gracza: " + this.hp1);
                const damage = 10 + Math.floor(enemyPower / 10);
                this.hp1 = Math.max(0, this.hp1 - damage);
            }
            this.powerBar2.width = "0%";
            this.phase = "attack";
            this.attackButton.textBlock.text = "Atak";
            this.updateBars();
        }, 1000);
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

    run() {
        this.engine.runRenderLoop(() => this.scene.render());
        window.addEventListener("resize", () => this.engine.resize());
    }
}


new Game("renderCanvas");
