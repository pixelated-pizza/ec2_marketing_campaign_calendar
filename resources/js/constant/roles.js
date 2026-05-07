export const ROLES = {
    VIEWER: "Viewer",
    EDITOR: "Editor",
    ADMIN: "Administrator",
};

export const ROUTE_PERMISSIONS = {
    UserMgmt: [ROLES.ADMIN],
 
    Campaigns:          [ROLES.EDITOR, ROLES.ADMIN],
    WebsiteCampaigns:   [ROLES.EDITOR, ROLES.ADMIN],
    WebsiteSale:        [ROLES.EDITOR, ROLES.ADMIN],
    WebsitePromo:       [ROLES.EDITOR, ROLES.ADMIN],
    WebsitePromoArchive:[ROLES.EDITOR, ROLES.ADMIN],
    WebsiteSaleArchive: [ROLES.EDITOR, ROLES.ADMIN],
    KeyMarketingDates:  [ROLES.EDITOR, ROLES.ADMIN],
    CategoryFeaturedSkus:[ROLES.EDITOR, ROLES.ADMIN],
 
    Dashboard:     [ROLES.VIEWER, ROLES.EDITOR, ROLES.ADMIN],
    ResetPassword: [ROLES.VIEWER, ROLES.EDITOR, ROLES.ADMIN],
};