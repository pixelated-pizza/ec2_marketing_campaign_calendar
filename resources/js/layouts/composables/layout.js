import { computed, reactive } from 'vue';

// Must match the `xl` breakpoint in resources/css/assets/tailwind.css
const DESKTOP_BREAKPOINT = 1200;
const THEME_STORAGE_KEY = 'app_theme';

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'static'
});

const layoutState = reactive({
    staticMenuInactive: false, // desktop: sidebar collapsed to the icon rail
    overlayMenuActive: false,
    mobileMenuActive: false, // mobile: sidebar drawer open
    sidebarHovered: false, // desktop: collapsed sidebar temporarily expanded on hover
    profileSidebarVisible: false,
    configSidebarVisible: false,
    sidebarExpanded: false,
    menuHoverActive: false,
    activeMenuItem: null,
    activePath: null
});

// Restore the last chosen theme
try {
    if (localStorage.getItem(THEME_STORAGE_KEY) === 'dark') {
        layoutConfig.darkTheme = true;
        document.documentElement.classList.add('app-dark');
    }
} catch {
    /* storage unavailable */
}

// Close the mobile drawer when the window grows to desktop size
window.addEventListener('resize', () => {
    if (window.innerWidth >= DESKTOP_BREAKPOINT) {
        layoutState.mobileMenuActive = false;
    }
});

export function useLayout() {
    const isDesktop = () => window.innerWidth >= DESKTOP_BREAKPOINT;

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle('app-dark', layoutConfig.darkTheme);
        try {
            localStorage.setItem(THEME_STORAGE_KEY, layoutConfig.darkTheme ? 'dark' : 'light');
        } catch {
            /* storage unavailable */
        }
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();
            return;
        }

        document.startViewTransition(() => executeDarkModeToggle());
    };

    const toggleMenu = () => {
        if (isDesktop()) {
            if (layoutConfig.menuMode === 'static') {
                layoutState.staticMenuInactive = !layoutState.staticMenuInactive;
                layoutState.sidebarHovered = false;
            }

            if (layoutConfig.menuMode === 'overlay') {
                layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
            }
        } else {
            layoutState.mobileMenuActive = !layoutState.mobileMenuActive;
        }
    };

    const toggleConfigSidebar = () => {
        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const hideMobileMenu = () => {
        layoutState.mobileMenuActive = false;
    };

    const setSidebarHovered = (value) => {
        layoutState.sidebarHovered = value;
    };

    const changeMenuMode = (event) => {
        layoutConfig.menuMode = event.value;
        layoutState.staticMenuInactive = false;
        layoutState.mobileMenuActive = false;
        layoutState.sidebarExpanded = false;
        layoutState.menuHoverActive = false;
        layoutState.anchored = false;
    };

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const hasOpenOverlay = computed(() => layoutState.overlayMenuActive);
    const isMobileOpen = computed(() => layoutState.mobileMenuActive);

    // true when the sidebar shows labels (expanded, hovered while collapsed, or mobile drawer)
    const isSidebarWide = computed(
        () => !layoutState.staticMenuInactive || layoutState.sidebarHovered || layoutState.mobileMenuActive
    );

    return {
        layoutConfig,
        layoutState,
        isDarkTheme,
        isMobileOpen,
        isSidebarWide,
        toggleDarkMode,
        toggleConfigSidebar,
        toggleMenu,
        hideMobileMenu,
        setSidebarHovered,
        changeMenuMode,
        isDesktop,
        hasOpenOverlay
    };
}
