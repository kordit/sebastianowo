class AjaxHelper {
    static sendRequest(url, method, data) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response.data?.message || 'Unknown error');
                        }
                    } catch (error) {
                        reject('Invalid JSON response');
                    }
                } else {
                    reject(`HTTP error: ${xhr.status}`);
                }
            };

            xhr.onerror = () => reject('Request failed');

            const encodedData = new URLSearchParams(data).toString();
            xhr.send(encodedData);
        });
    }
}