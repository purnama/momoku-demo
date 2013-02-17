<?php
/**
 *  Momoku Glue Stack Framework
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL 3.0. For more information, see
 * <http://github.com/purnama/momoku>.
 *
 * @license <http://www.gnu.org/copyleft/lesser.html> LGPL 3.0
 * @link    http://github.com/purnama/momoku
 * @copyright Copyright (c) 2013 Momoku (http://github.com/purnama/momoku)
 */
namespace Album\Controller;

use Demo\Dao\AlbumDao;
use Demo\Entity\Album;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Album\Form\AlbumForm;
use Album\Form\AlbumFormFilter;

/**
 *
 * @author  Arthur Purnama <arthur@purnama.de>
 */
class AlbumController extends AbstractActionController
{
    /**
     * @var \Demo\Dao\AlbumDao
     */
    private $dao;

    /**
     * @var \Album\Form\AlbumFormFilter
     */
    private $filter;

    /**
     * @var \Album\Form\AlbumForm
     */
    private $form;

    /**
     * @Inject
     * @param \Demo\Dao\AlbumDao $dao
     */
    public function __construct(AlbumDao $dao, AlbumForm $form, AlbumFormFilter $filter)
    {
        $this->dao = $dao;
        $this->filter = $filter;
        $this->form = $form;
    }

    public function indexAction()
    {
        return new ViewModel(array(
            'albums' => $this->dao->findAll(),
        ));
    }

    public function addAction()
    {
        $this->form->get('submit')->setValue('Add');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setInputFilter($this->filter->getInputFilter());
            $this->form->setData($request->getPost());

            if ($this->form->isValid()) {
                $album = new Album();
                $this->exchangeArrayToObject($album, $this->form->getData());
                $this->dao->persist($album);
                $this->dao->flush();

                // Redirect to list of albums
                return $this->redirect()->toRoute('album');
            }
        }
        return array('form' => $this->form);
    }

    public function editAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('album', array(
                'action' => 'add'
            ));
        }
        /** @var $album \Demo\Entity\Album */
        $album = $this->dao->findById($id);
        $this->form->bind($this->exchangeArrayFromObject($album));
        $this->form->get('submit')->setAttribute('value', 'Edit');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->form->setInputFilter($this->filter->getInputFilter());
            $this->form->setData($request->getPost());

            if ($this->form->isValid()) {
                $this->exchangeArrayToObject($album, $this->form->getData());
                $this->dao->persist($album);
                $this->dao->flush();

                // Redirect to list of albums
                return $this->redirect()->toRoute('album');
            }
        }

        return array(
            'id' => $id,
            'form' => $this->form,
        );
    }

    public function deleteAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('album');
        }

        $album = $this->dao->findById($id);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');

            if ($del == 'Yes') {
                $this->dao->remove($album);
                $this->dao->flush();
            }

            // Redirect to list of albums
            return $this->redirect()->toRoute('album');
        }

        return array(
            'id' => $id,
            'album' => $album
        );
    }

    private function exchangeArrayToObject(Album $album, $data)
    {
        $album->setArtist((isset($data['artist'])) ? $data['artist'] : null);
        $album->setTitle((isset($data['title'])) ? $data['title'] : null);
    }

    private function exchangeArrayFromObject(Album $album)
    {
        return new \ArrayObject(array(
            'artist' => $album->getArtist(),
            'title' => $album->getTitle()
        ));
    }
}