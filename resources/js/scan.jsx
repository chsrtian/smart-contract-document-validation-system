// filepath: c:\Users\PC\final_capstone\resources\js\scan.jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import ScanDocuments from './components/ScanDocuments';

console.log('React scan.jsx loaded');

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded in scan.jsx');
    
    const scanRoot = document.getElementById('scan-root');
    console.log('scanRoot element:', scanRoot);
    
    if (scanRoot) {
        try {
            console.log('Attempting to create React root');
            const root = createRoot(scanRoot);
            console.log('React root created successfully');
            root.render(<ScanDocuments />);
            console.log('ScanDocuments component rendered');
        } catch (error) {
            console.error('Error rendering React component:', error);
        }
    } else {
        console.error('scan-root element not found in the DOM');
    }
});