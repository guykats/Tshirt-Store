import { createRoot } from 'react-dom/client';
import './i18n';
import App from './App';

const container = document.getElementById('app');
createRoot(container).render(<App />);
