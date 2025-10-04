I would like to create a WordPress Security Plugin, which strictly follows the WordPress Codex Standards and Guidelines - to help me achieve the following tasks.

1. Secures wp-cron to protect against exploits like denial-of-service (DoS),
2. Helps better protect XML-RPC for a WordPress websites from Pingback Attacks,
3. Provides a secure and safe way to Rate Limit the WordPress Login Page.
4. The plugin should protect the disclosure of legit usernames using the REST API's endpoint /wp-json/wp/v2/users or something similar.
5. The plugin should implement Rate limits on Password Reset, to avoid its abuse.
6. The plugin Should Protect against the Lack of Rate Limiting on Contact and Registration Forms

On Activation of the Plugin, a Separe Menu Option in the Side panel should appear, below Plugins Menu.

The user and IP lockouts should be like 0, 30s, 1, 5 and then double the previous lockout time [in minuts] on each failed attempt. After 80 mins it should be locked out for 24 hrs. After a repeat of 24 hrs lockout, the IP should be locked permanently unless an Admin Level user Explicitly allows it. I suggest that lock should be applied to the IP address only as we may get a legitimate user account locked out due to some mischievous user - so think we need to keep him safe?

The plugin should be Named 'SecureWP Pro', and Author should be 'Tanveer Malik'. The plugins files should be structured as per the best practices and recommendations of WordPress Codex Standard. Plugin URI and Author UI should be left blank.

The system should log each failed attempt and the Admin should be notified as well.
The plugin is desired to have an interface to easily control the behavior like enable/disable the features when needed.

https://chat.deepseek.com/a/chat/s/23b0f41f-0468-4012-94d0-feb3ac5a6a60