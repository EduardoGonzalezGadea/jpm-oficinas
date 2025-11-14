function printReport() {
    const url = new URL(window.location.href);
    url.searchParams.set('print', 'true');

    const printWindow = window.open(url, '_blank');

    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 1000);
    };
}
