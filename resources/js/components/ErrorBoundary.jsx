import { Component } from 'react';
import { withTranslation } from 'react-i18next';

class ErrorBoundaryBase extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, info) {
        // No backend log call here on purpose: the app just crashed, so a network
        // request from this same broken render tree isn't a reliable place to put
        // observability. The browser console is enough for now.
        console.error('Unhandled error in page render:', error, info);
    }

    handleReset = () => {
        this.setState({ hasError: false });
        window.location.assign('/');
    };

    render() {
        if (!this.state.hasError) {
            return this.props.children;
        }

        const { t } = this.props;

        return (
            <div className="mx-auto max-w-md px-6 py-24 text-center" role="alert">
                <p className="mb-3 text-xs tracking-[0.3em] text-brass uppercase">{t('error_boundary_eyebrow')}</p>
                <h1 className="mb-4 font-serif text-2xl">{t('error_boundary_title')}</h1>
                <p className="mb-8 text-ink-soft">{t('error_boundary_message')}</p>
                <button
                    onClick={this.handleReset}
                    className="rounded bg-ink px-5 py-2.5 text-sm text-white"
                >
                    {t('error_boundary_home')}
                </button>
            </div>
        );
    }
}

export default withTranslation()(ErrorBoundaryBase);
