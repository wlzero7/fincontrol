const authScreen = document.getElementById("auth-screen");
const app = document.getElementById("app");

const loginUser = document.getElementById("login-user");
const loginPassword = document.getElementById("login-password");

const registerUser = document.getElementById("register-user");
const registerEmail = document.getElementById("register-email");
const registerPassword = document.getElementById("register-password");

const loginArea = document.getElementById("login-area");
const registerArea = document.getElementById("register-area");
const verifyArea = document.getElementById("verify-area");

const authTitle = document.getElementById("auth-title");
const authSubtitle = document.getElementById("auth-subtitle");

const demoEmail = document.getElementById("demo-email");
const demoCode = document.getElementById("demo-code");
const verifyCode = document.getElementById("verify-code");

const welcomeUser = document.getElementById("welcome-user");

const form = document.getElementById("transaction-form");
const list = document.getElementById("transaction-list");

const balanceEl = document.getElementById("balance");
const incomeEl = document.getElementById("income");
const expenseEl = document.getElementById("expense");
const totalCountEl = document.getElementById("total-count");

const descriptionInput = document.getElementById("description");
const amountInput = document.getElementById("amount");
const typeInput = document.getElementById("type");
const categoryInput = document.getElementById("category");
const dateInput = document.getElementById("date");

const searchInput = document.getElementById("search");
const filterButtons = document.querySelectorAll(".filter-btn");
const themeToggle = document.getElementById("theme-toggle");

const defaultCategories = [
    "Salário",
    "Freelance",
    "Alimentação",
    "Transporte",
    "Lazer",
    "Investimento",
    "Faculdade",
    "Saúde",
    "Outros"
];

let pendingUser = null;
let generatedCode = "";
let currentUser = localStorage.getItem("currentUser");
let currentFilter = "all";
let financeChart;
let categoryChart;

function showLogin() {
    loginArea.classList.remove("hidden");
    registerArea.classList.add("hidden");
    verifyArea.classList.add("hidden");

    authTitle.textContent = "Entrar na conta";
    authSubtitle.textContent = "Acesse seu painel financeiro.";
}

function showRegister() {
    loginArea.classList.add("hidden");
    registerArea.classList.remove("hidden");
    verifyArea.classList.add("hidden");

    authTitle.textContent = "Criar conta";
    authSubtitle.textContent = "Preencha os dados para iniciar.";
}

function showVerify() {
    loginArea.classList.add("hidden");
    registerArea.classList.add("hidden");
    verifyArea.classList.remove("hidden");

    authTitle.textContent = "Confirmar e-mail";
    authSubtitle.textContent = "Digite o código de verificação.";
}

function createCode() {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let code = "";

    for (let i = 0; i < 6; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    return code;
}

function generateVerificationCode() {
    const name = registerUser.value.trim();
    const email = registerEmail.value.trim();
    const password = registerPassword.value.trim();

    if (!name || !email || !password) {
        alert("Preencha todos os campos.");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        alert("Digite um e-mail válido.");
        return;
    }

    const users = JSON.parse(localStorage.getItem("users")) || {};

    if (users[name]) {
        alert("Este usuário já existe.");
        return;
    }

    generatedCode = createCode();

    pendingUser = {
        name,
        email,
        password,
        verified: false
    };

    demoEmail.textContent = email;
    demoCode.textContent = generatedCode;

    showVerify();
}

function confirmCode() {
    const typedCode = verifyCode.value.trim().toUpperCase();

    if (typedCode !== generatedCode) {
        alert("Código incorreto.");
        return;
    }

    const users = JSON.parse(localStorage.getItem("users")) || {};

    users[pendingUser.name] = {
        email: pendingUser.email,
        password: pendingUser.password,
        verified: true
    };

    localStorage.setItem("users", JSON.stringify(users));

    alert("Cadastro confirmado com sucesso!");

    pendingUser = null;
    generatedCode = "";

    registerUser.value = "";
    registerEmail.value = "";
    registerPassword.value = "";
    verifyCode.value = "";

    showLogin();
}

function login() {
    const name = loginUser.value.trim();
    const password = loginPassword.value.trim();

    const users = JSON.parse(localStorage.getItem("users")) || {};

    if (!users[name]) {
        alert("Usuário não encontrado.");
        return;
    }

    if (users[name].password !== password) {
        alert("Senha incorreta.");
        return;
    }

    if (!users[name].verified) {
        alert("Conta ainda não verificada.");
        return;
    }

    currentUser = name;
    localStorage.setItem("currentUser", currentUser);

    startApp();
}

function logout() {
    localStorage.removeItem("currentUser");
    location.reload();
}

function userKey(key) {
    return `${currentUser}_${key}`;
}

function getTransactions() {
    return JSON.parse(localStorage.getItem(userKey("transactions"))) || [];
}

function setTransactions(data) {
    localStorage.setItem(userKey("transactions"), JSON.stringify(data));
}

function getCategories() {
    return JSON.parse(localStorage.getItem(userKey("categories"))) || defaultCategories;
}

function setCategories(data) {
    localStorage.setItem(userKey("categories"), JSON.stringify(data));
}

function getGoal() {
    return JSON.parse(localStorage.getItem(userKey("goal"))) || null;
}

function setGoal(data) {
    localStorage.setItem(userKey("goal"), JSON.stringify(data));
}

let transactions = currentUser ? getTransactions() : [];

function startApp() {
    authScreen.style.display = "none";
    app.style.display = "block";

    welcomeUser.textContent = `Bem-vindo, ${currentUser}. Vamos organizar suas finanças hoje?`;

    transactions = getTransactions();

    dateInput.valueAsDate = new Date();

    loadTheme();
    renderCategories();
    renderTransactions();
}

if (currentUser) {
    startApp();
}

function formatCurrency(value) {
    return value.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });
}

function formatDate(date) {
    const [year, month, day] = date.split("-");
    return `${day}/${month}/${year}`;
}

function renderCategories() {
    const categories = getCategories();

    categoryInput.innerHTML = "";

    categories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        categoryInput.appendChild(option);
    });

    const categoryList = document.getElementById("category-list");
    categoryList.innerHTML = "";

    categories.forEach(category => {
        const span = document.createElement("span");
        span.textContent = category;
        categoryList.appendChild(span);
    });
}

function addCategory() {
    const input = document.getElementById("new-category");
    const newCategory = input.value.trim();

    if (!newCategory) {
        alert("Digite uma categoria.");
        return;
    }

    const categories = getCategories();

    if (categories.includes(newCategory)) {
        alert("Essa categoria já existe.");
        return;
    }

    categories.push(newCategory);
    setCategories(categories);

    input.value = "";
    renderCategories();
}

function updateValues() {
    const income = transactions
        .filter(t => t.type === "income")
        .reduce((acc, t) => acc + t.amount, 0);

    const expense = transactions
        .filter(t => t.type === "expense")
        .reduce((acc, t) => acc + t.amount, 0);

    const balance = income - expense;

    incomeEl.textContent = formatCurrency(income);
    expenseEl.textContent = formatCurrency(expense);
    balanceEl.textContent = formatCurrency(balance);
    totalCountEl.textContent = transactions.length;

    updateAlert(income, expense, balance);
    updateGoal(balance);
    updateCharts(income, expense);
}

function updateAlert(income, expense, balance) {
    const alertBox = document.getElementById("financial-alert");

    if (transactions.length === 0) {
        alertBox.textContent = "Adicione sua primeira movimentação para começar a receber análises financeiras.";
        return;
    }

    if (expense > income) {
        alertBox.textContent = "⚠️ Atenção: suas despesas estão maiores que suas receitas.";
    } else if (balance > 0) {
        alertBox.textContent = "✅ Ótimo! Você está com saldo positivo.";
    } else {
        alertBox.textContent = "📊 Seu saldo está zerado. Continue acompanhando suas movimentações.";
    }
}

function getFilteredTransactions() {
    const search = searchInput.value.toLowerCase();

    return transactions.filter(t => {
        const matchesType = currentFilter === "all" || t.type === currentFilter;

        const matchesSearch =
            t.description.toLowerCase().includes(search) ||
            t.category.toLowerCase().includes(search);

        return matchesType && matchesSearch;
    });
}

function renderTransactions() {
    const filtered = getFilteredTransactions();

    list.innerHTML = "";

    if (filtered.length === 0) {
        list.innerHTML = `<p class="empty">Nenhuma movimentação encontrada.</p>`;
        updateValues();
        setTransactions(transactions);
        return;
    }

    filtered.forEach(transaction => {
        const li = document.createElement("li");
        li.classList.add("transaction");

        const valueClass = transaction.type === "income" ? "income" : "expense";
        const signal = transaction.type === "income" ? "+" : "-";

        li.innerHTML = `
            <div>
                <strong>${transaction.description}</strong>
                <small>${transaction.category}</small>
            </div>

            <div>
                <small>${formatDate(transaction.date)}</small>
            </div>

            <div>
                <span class="${valueClass}">
                    ${signal} ${formatCurrency(transaction.amount)}
                </span>
            </div>

            <div>
                <small>${transaction.type === "income" ? "Receita" : "Despesa"}</small>
            </div>

            <button onclick="removeTransaction(${transaction.id})">Excluir</button>
        `;

        list.appendChild(li);
    });

    updateValues();
    setTransactions(transactions);
}

function removeTransaction(id) {
    transactions = transactions.filter(t => t.id !== id);
    renderTransactions();
}

function clearAll() {
    if (!confirm("Tem certeza que deseja apagar todo o histórico?")) {
        return;
    }

    transactions = [];
    renderTransactions();
}

form.addEventListener("submit", event => {
    event.preventDefault();

    const transaction = {
        id: Date.now(),
        description: descriptionInput.value.trim(),
        amount: Number(amountInput.value),
        type: typeInput.value,
        category: categoryInput.value,
        date: dateInput.value
    };

    if (!transaction.description || transaction.amount <= 0 || !transaction.date) {
        alert("Preencha todos os campos corretamente.");
        return;
    }

    transactions.push(transaction);

    form.reset();
    dateInput.valueAsDate = new Date();

    renderTransactions();
});

filterButtons.forEach(button => {
    button.addEventListener("click", () => {
        filterButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        currentFilter = button.dataset.filter;

        renderTransactions();
    });
});

searchInput.addEventListener("input", renderTransactions);

function updateCharts(income, expense) {
    const financeCtx = document.getElementById("finance-chart");
    const categoryCtx = document.getElementById("category-chart");

    if (financeChart) financeChart.destroy();
    if (categoryChart) categoryChart.destroy();

    financeChart = new Chart(financeCtx, {
        type: "doughnut",
        data: {
            labels: ["Receitas", "Despesas"],
            datasets: [{
                data: [income, expense],
                backgroundColor: ["#22c55e", "#ef4444"]
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: getComputedStyle(document.body).getPropertyValue("--text")
                    }
                }
            }
        }
    });

    const expensesByCategory = {};

    transactions
        .filter(t => t.type === "expense")
        .forEach(t => {
            expensesByCategory[t.category] =
                (expensesByCategory[t.category] || 0) + t.amount;
        });

    categoryChart = new Chart(categoryCtx, {
        type: "bar",
        data: {
            labels: Object.keys(expensesByCategory),
            datasets: [{
                label: "Despesas por Categoria",
                data: Object.values(expensesByCategory),
                backgroundColor: "#38bdf8"
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: getComputedStyle(document.body).getPropertyValue("--text")
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue("--text")
                    }
                },
                y: {
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue("--text")
                    }
                }
            }
        }
    });
}

function saveGoal() {
    const name = document.getElementById("goal-name").value.trim();
    const value = Number(document.getElementById("goal-value").value);

    if (!name || value <= 0) {
        alert("Preencha a meta corretamente.");
        return;
    }

    setGoal({ name, value });

    document.getElementById("goal-name").value = "";
    document.getElementById("goal-value").value = "";

    updateValues();
}

function updateGoal(balance) {
    const goal = getGoal();

    const title = document.getElementById("goal-title");
    const info = document.getElementById("goal-info");
    const progress = document.getElementById("goal-progress");

    if (!goal) {
        title.textContent = "Nenhuma meta definida";
        info.textContent = "Defina uma meta para acompanhar seu progresso.";
        progress.style.width = "0%";
        return;
    }

    const percent = Math.min((balance / goal.value) * 100, 100);

    title.textContent = goal.name;
    info.textContent = `${formatCurrency(balance)} de ${formatCurrency(goal.value)} - ${percent.toFixed(1)}%`;
    progress.style.width = `${percent}%`;
}

function exportCSV() {
    let csv = "Descrição,Categoria,Tipo,Data,Valor\n";

    transactions.forEach(t => {
        csv += `${t.description},${t.category},${t.type},${t.date},${t.amount}\n`;
    });

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");

    link.href = URL.createObjectURL(blob);
    link.download = "fincontrol-transacoes.csv";
    link.click();
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text("Relatório Financeiro - FinControl", 20, 20);

    doc.setFontSize(12);
    doc.text(`Usuário: ${currentUser}`, 20, 35);
    doc.text(`Total de movimentações: ${transactions.length}`, 20, 45);

    let y = 60;

    transactions.forEach(t => {
        doc.text(
            `${t.description} | ${t.category} | ${t.type} | ${formatCurrency(t.amount)}`,
            20,
            y
        );

        y += 10;

        if (y > 280) {
            doc.addPage();
            y = 20;
        }
    });

    doc.save("relatorio-fincontrol.pdf");
}

themeToggle.addEventListener("click", () => {
    document.body.classList.toggle("light");

    const theme = document.body.classList.contains("light") ? "light" : "dark";

    localStorage.setItem(userKey("theme"), theme);

    renderTransactions();
});

function loadTheme() {
    const theme = localStorage.getItem(userKey("theme"));

    if (theme === "light") {
        document.body.classList.add("light");
    }
}