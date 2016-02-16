
"""Tests for container.container.py classes"""
import os
import unittest

from kd_common.panel import Panel

TEST_DATA_PATH = os.path.join(os.path.dirname(os.path.realpath(__file__)), 'data')
TEST_PANEL = 'cPanel'


class TestCpanel(unittest.TestCase):
    def setUp(self):
        self.user_domain_path = os.path.join(TEST_DATA_PATH, 'cpanel/etc/userdatadomains') + ';' \
                                + os.path.join(TEST_DATA_PATH, 'cpanel/var/cpanel/{user}/cache')
        self.panel = Panel()
        self.cpanel = self.panel.load(TEST_PANEL)

    def test_get_current_panel(self):
        panel = self.panel.get_current()
        self.assertEqual(TEST_PANEL, panel)

    def test_get_user_domains(self):
        results = [('usert.com', '/home/usert/public_html'),
                   ('drupal.usert.com', '/home/usert/public_html/drupal'),
                   ('gallery3.usert.com', '/home/usert/public_html/gallery3'),
                   ('joomla.usert.com', '/home/usert/public_html/joomla'),
                   ('magento.usert.com', '/home/usert/public_html/magento'),
                   ('nginx.usert.com', '/home/usert/public_html/nginx'),
                   ('opencart.usert.com', '/home/usert/public_html/opencart'),
                   ('phpbb.usert.com', '/home/usert/public_html/phpbb'),
                   ('redmine.usert.com', '/home/usert/public_html/redmine'),
                   ('sub.usert.com', '/home/usert/public_html/sub'),
                   ('sugarcrm.usert.com', '/home/usert/public_html/sugarcrm'),
                   ('wp.usert.com', '/home/usert/public_html/wp')]
        user_domains = self.cpanel.get_user_domains('usert', self.user_domain_path)
        self.assertEqual(results, user_domains)

    def test_get_domain_docroot(self):
        docroot = self.cpanel.get_domain_docroot('usert', 'drupal.usert.com')
        self.assertEqual('/home/usert/public_html/drupal', docroot)

        docroot = self.cpanel.get_domain_docroot('clman1', 'clman1.com')
        self.assertEqual('/home/clman1/public_html', docroot)


if __name__ == '__main__':
    unittest.main()
