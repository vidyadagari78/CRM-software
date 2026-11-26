let currentPropPrice = 0;

function formatPriceINR(amount) {
    if (!amount) return '₹0';
    if (amount >= 10000000) return '₹' + (amount / 10000000).toFixed(2) + ' Cr';
    if (amount >= 100000) return '₹' + (amount / 100000).toFixed(2) + ' L';
    return '₹' + Math.round(amount).toLocaleString('en-IN');
}

function openEmiCalc(prop) {
    currentPropPrice = prop.price || 0;
    document.getElementById('emiModalTitle').textContent = 'EMI & Commission — ' + prop.title;
    document.getElementById('emiModal').style.display = 'flex';
    recalcEmi();
}

function closeEmiCalc() {
    document.getElementById('emiModal').style.display = 'none';
}

function recalcEmi() {
    const down = parseFloat(document.getElementById('emiDown').value) || 0;
    const rate = parseFloat(document.getElementById('emiRate').value) || 1;
    const years = parseFloat(document.getElementById('emiYears').value) || 1;
    const comm = parseFloat(document.getElementById('emiComm').value) || 0;

    const principal = currentPropPrice * (1 - down / 100);
    const monthlyRate = rate / 12 / 100;
    const n = years * 12;
    const emi = monthlyRate > 0
        ? (principal * monthlyRate * Math.pow(1 + monthlyRate, n)) / (Math.pow(1 + monthlyRate, n) - 1)
        : principal / n;
    const brokerage = (currentPropPrice * comm) / 100;

    document.getElementById('emiMonthly').textContent = '₹' + Math.round(emi).toLocaleString('en-IN') + '/mo';
    document.getElementById('emiLoan').textContent = formatPriceINR(principal);
    document.getElementById('emiBrokerage').textContent = formatPriceINR(brokerage);
}

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('crmSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
});
